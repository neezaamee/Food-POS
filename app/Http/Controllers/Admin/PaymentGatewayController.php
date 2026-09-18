<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewayTransaction;
use App\Models\SystemSetting;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    /**
     * Display Payment Gateways dashboard, credentials forms, and transaction logs
     */
    public function index(PaymentManager $paymentManager): View
    {
        $providers = $paymentManager->getAvailableProviders();
        $recentTransactions = PaymentGatewayTransaction::with('order')
            ->latest()
            ->paginate(15);

        $settings = [
            'default_provider' => SystemSetting::get('default_digital_payment_provider', 'jazzcash'),

            // JazzCash
            'jazzcash_enabled' => (bool) SystemSetting::get('jazzcash_enabled', false),
            'jazzcash_environment' => SystemSetting::get('jazzcash_environment', 'sandbox'),
            'jazzcash_merchant_id' => SystemSetting::get('jazzcash_merchant_id', ''),
            'jazzcash_password' => SystemSetting::get('jazzcash_password', ''),
            'jazzcash_integrity_salt' => SystemSetting::get('jazzcash_integrity_salt', ''),

            // EasyPaisa
            'easypaisa_enabled' => (bool) SystemSetting::get('easypaisa_enabled', false),
            'easypaisa_environment' => SystemSetting::get('easypaisa_environment', 'sandbox'),
            'easypaisa_store_id' => SystemSetting::get('easypaisa_store_id', ''),
            'easypaisa_hash_key' => SystemSetting::get('easypaisa_hash_key', ''),
            'easypaisa_account_number' => SystemSetting::get('easypaisa_account_number', ''),

            // NayaPay
            'nayapay_enabled' => (bool) SystemSetting::get('nayapay_enabled', false),
            'nayapay_environment' => SystemSetting::get('nayapay_environment', 'sandbox'),
            'nayapay_client_id' => SystemSetting::get('nayapay_client_id', ''),
            'nayapay_client_secret' => SystemSetting::get('nayapay_client_secret', ''),
            'nayapay_terminal_id' => SystemSetting::get('nayapay_terminal_id', ''),

            // Raast QR
            'raast_enabled' => (bool) SystemSetting::get('raast_enabled', true),
            'raast_environment' => SystemSetting::get('raast_environment', 'sandbox'),
            'raast_iban' => SystemSetting::get('raast_iban', 'PK00FOOD0000001234567890'),

            // Simulator
            'simulator_enabled' => (bool) SystemSetting::get('simulator_enabled', true),
        ];

        return view('admin.payments.index', compact('providers', 'recentTransactions', 'settings'));
    }

    /**
     * Save payment gateway credentials and environment settings
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $keys = [
            'default_digital_payment_provider',

            // JazzCash
            'jazzcash_enabled',
            'jazzcash_environment',
            'jazzcash_merchant_id',
            'jazzcash_password',
            'jazzcash_integrity_salt',

            // EasyPaisa
            'easypaisa_enabled',
            'easypaisa_environment',
            'easypaisa_store_id',
            'easypaisa_hash_key',
            'easypaisa_account_number',

            // NayaPay
            'nayapay_enabled',
            'nayapay_environment',
            'nayapay_client_id',
            'nayapay_client_secret',
            'nayapay_terminal_id',

            // Raast QR
            'raast_enabled',
            'raast_environment',
            'raast_iban',

            // Simulator
            'simulator_enabled',
        ];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                $val = $request->input($key);
                SystemSetting::set($key, is_null($val) ? '' : (string) $val, 'payments');
            } elseif (str_ends_with($key, '_enabled')) {
                // Checkbox unchecked
                SystemSetting::set($key, '0', 'payments');
            }
        }

        return back()->with('success', 'Payment gateway configurations and credentials updated successfully!');
    }

    /**
     * Trigger a diagnostic test transaction
     */
    public function testTransaction(Request $request, PaymentManager $paymentManager): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:jazzcash,easypaisa,nayapay,raast,simulator',
            'mobile' => 'required|string|min:10',
            'amount' => 'nullable|numeric|min:1',
            'channel' => 'nullable|string|in:push_request,dynamic_qr',
        ]);

        $provider = $request->input('provider');
        $mobile = $request->input('mobile');
        $amount = (float) $request->input('amount', 10.00);
        $channel = $request->input('channel', 'push_request');
        $testOrderNo = 'TEST-'.now()->format('His');

        $result = $paymentManager->initiatePayment(
            provider: $provider,
            amount: $amount,
            mobileNumber: $mobile,
            orderNumber: $testOrderNo,
            channel: $channel,
            meta: ['is_diagnostic_test' => true]
        );

        return response()->json([
            'success' => $result['ok'] ?? false,
            'result' => $result,
        ]);
    }

    /**
     * Simulate an interactive customer response (approved, rejected, timeout)
     */
    public function simulateAction(Request $request, PaymentManager $paymentManager): JsonResponse
    {
        $request->validate([
            'transaction_reference' => 'required|string',
            'action' => 'required|string|in:approve,reject,timeout',
            'reason' => 'nullable|string',
        ]);

        $txRef = $request->input('transaction_reference');
        $action = $request->input('action');
        $reason = $request->input('reason', 'Customer declined payment');

        if ($action === 'approve') {
            $tx = $paymentManager->completeTransaction($txRef, [
                'message' => 'Simulated approval confirmed via test sandbox trigger.',
                'simulated_at' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => true,
                'status' => 'completed',
                'message' => 'Simulated payment APPROVED! Transaction marked as completed.',
                'transaction' => $tx,
            ]);
        }

        if ($action === 'reject') {
            $tx = $paymentManager->failTransaction($txRef, $reason);

            return response()->json([
                'success' => true,
                'status' => 'failed',
                'message' => "Simulated payment REJECTED ({$reason}).",
                'transaction' => $tx,
            ]);
        }

        $tx = $paymentManager->timeoutTransaction($txRef);

        return response()->json([
            'success' => true,
            'status' => 'expired',
            'message' => 'Simulated payment TIMED OUT.',
            'transaction' => $tx,
        ]);
    }
}
