<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\PaymentGatewayTransaction;
use App\Models\SystemSetting;
use App\Services\Payments\Contracts\PaymentGatewayInterface;
use App\Services\Payments\Drivers\EasyPaisaDriver;
use App\Services\Payments\Drivers\JazzCashDriver;
use App\Services\Payments\Drivers\NayaPayDriver;
use App\Services\Payments\Drivers\PaymentSimulatorDriver;
use App\Services\Payments\Drivers\RaastQrDriver;
use App\Services\SaaS\TenantContext;
use Illuminate\Support\Facades\Log;

class PaymentManager
{
    protected array $drivers = [];

    public function __construct()
    {
        $this->registerDriver('jazzcash', new JazzCashDriver);
        $this->registerDriver('easypaisa', new EasyPaisaDriver);
        $this->registerDriver('nayapay', new NayaPayDriver);
        $this->registerDriver('raast', new RaastQrDriver);
        $this->registerDriver('simulator', new PaymentSimulatorDriver);
    }

    public function registerDriver(string $name, PaymentGatewayInterface $driver): void
    {
        $this->drivers[$name] = $driver;
    }

    public function driver(?string $name = null): PaymentGatewayInterface
    {
        $name = $name ?: SystemSetting::get('default_digital_payment_provider', 'jazzcash');

        if (! isset($this->drivers[$name])) {
            // Default to simulator or jazzcash
            $name = isset($this->drivers['jazzcash']) ? 'jazzcash' : 'simulator';
        }

        return $this->drivers[$name];
    }

    /**
     * Get list of all providers with status and configuration state
     */
    public function getAvailableProviders(): array
    {
        $providers = [];

        foreach ($this->drivers as $name => $driver) {
            $enabledKey = "{$name}_enabled";
            $envKey = "{$name}_environment";

            // Raast and simulator enabled by default if not set
            $defaultEnabled = ($name === 'simulator' || $name === 'raast');

            $isEnabled = (bool) SystemSetting::get($enabledKey, $defaultEnabled);
            $env = SystemSetting::get($envKey, 'sandbox');

            $providers[$name] = [
                'name' => $name,
                'display_name' => $driver->getDisplayName(),
                'enabled' => $isEnabled,
                'environment' => $env,
                'is_sandbox' => ($env === 'sandbox'),
                'is_configured' => $driver->isConfigured(),
            ];
        }

        return $providers;
    }

    /**
     * Initiate a digital payment and record transaction entry
     */
    public function initiatePayment(
        string $provider,
        float $amount,
        string $mobileNumber,
        string $orderNumber,
        string $channel = 'push_request',
        ?Order $order = null,
        array $meta = []
    ): array {
        $driver = $this->driver($provider);
        $cleanPhone = preg_replace('/[^0-9]/', '', $mobileNumber);

        // Dispatch to driver
        if ($channel === 'dynamic_qr') {
            $result = $driver->generateDynamicQr($amount, $orderNumber, array_merge($meta, ['simulated_provider' => $provider]));
        } else {
            $result = $driver->initiatePushPayment($amount, $cleanPhone, $orderNumber, array_merge($meta, ['simulated_provider' => $provider]));
        }

        $txRef = $result['transaction_id'] ?? 'TX-'.uniqid();
        $status = $result['status'] ?? ($result['ok'] ? 'pending_customer' : 'failed');

        // Persist transaction record
        $tenantId = TenantContext::hasTenant() ? TenantContext::getTenantId() : ($order?->tenant_id ?? null);

        $tx = PaymentGatewayTransaction::create([
            'tenant_id' => $tenantId,
            'order_id' => $order?->id,
            'order_number' => $orderNumber,
            'provider' => $provider,
            'channel' => $channel,
            'transaction_reference' => $txRef,
            'mobile_number' => $cleanPhone,
            'amount' => $amount,
            'fee_amount' => 0.00,
            'currency' => 'PKR',
            'status' => $status,
            'environment' => $driver->isSandbox() ? 'sandbox' : 'production',
            'gateway_response_code' => $result['ok'] ? '000' : '999',
            'gateway_response_message' => $result['message'] ?? null,
            'request_payload' => [
                'provider' => $provider,
                'channel' => $channel,
                'mobile' => $cleanPhone,
                'amount' => $amount,
                'order_number' => $orderNumber,
            ],
            'response_payload' => $result,
        ]);

        $result['db_transaction_id'] = $tx->id;
        $result['transaction_reference'] = $txRef;

        return $result;
    }

    /**
     * Mark a pending transaction completed
     */
    public function completeTransaction(string $transactionRef, array $extraResponse = []): ?PaymentGatewayTransaction
    {
        $tx = PaymentGatewayTransaction::where('transaction_reference', $transactionRef)->first();

        if (! $tx) {
            return null;
        }

        $tx->status = 'completed';
        $tx->paid_at = now();
        $tx->gateway_response_code = '0000';
        $tx->gateway_response_message = $extraResponse['message'] ?? 'Payment confirmed and verified successfully.';

        $existingPayload = $tx->response_payload ?? [];
        $tx->response_payload = array_merge($existingPayload, ['verification' => $extraResponse]);
        $tx->save();

        Log::info("Payment transaction {$transactionRef} completed successfully for Order #{$tx->order_number}");

        return $tx;
    }

    /**
     * Mark transaction as failed or rejected
     */
    public function failTransaction(string $transactionRef, string $reason = 'Payment declined by customer.'): ?PaymentGatewayTransaction
    {
        $tx = PaymentGatewayTransaction::where('transaction_reference', $transactionRef)->first();

        if (! $tx) {
            return null;
        }

        $tx->status = 'failed';
        $tx->gateway_response_code = 'FAILED';
        $tx->gateway_response_message = $reason;
        $tx->save();

        Log::warning("Payment transaction {$transactionRef} failed: {$reason}");

        return $tx;
    }

    /**
     * Mark transaction as timed out
     */
    public function timeoutTransaction(string $transactionRef): ?PaymentGatewayTransaction
    {
        $tx = PaymentGatewayTransaction::where('transaction_reference', $transactionRef)->first();

        if (! $tx) {
            return null;
        }

        $tx->status = 'expired';
        $tx->gateway_response_code = 'TIMEOUT';
        $tx->gateway_response_message = 'Customer prompt timed out with no response.';
        $tx->save();

        return $tx;
    }
}
