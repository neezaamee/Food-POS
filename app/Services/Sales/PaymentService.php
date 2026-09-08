<?php

namespace App\Services\Sales;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\OrderPayment;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Record a payment for an order (supports split payments)
     */
    public function recordPayment(Order $order, string $method, float $amount, ?string $reference = null, ?int $userId = null): OrderPayment
    {
        return DB::transaction(function () use ($order, $method, $amount, $reference, $userId) {
            if ($amount <= 0) {
                throw new Exception('Payment amount must be greater than zero.');
            }

            $currentBalance = (float) $order->balance_amount;
            if ($amount > ($currentBalance + 0.01) && $method !== 'cash') {
                throw new Exception("Non-cash payment amount ({$amount}) cannot exceed order balance ({$currentBalance}).");
            }

            // Map method to ledger account
            $accountCode = ($method === 'bank' || $method === 'card' || $method === 'digital') ? '1120' : '1110';
            $account = Account::where('code', $accountCode)->first();

            $payment = $order->payments()->create([
                'payment_method' => strtolower($method),
                'amount' => $amount,
                'payment_reference' => $reference,
                'account_id' => $account?->id,
                'received_by' => $userId ?? auth()->id(),
            ]);

            // Update order paid and balance
            $totalPaid = (float) $order->payments()->sum('amount');
            $order->paid_amount = $totalPaid;
            $order->balance_amount = max(0, (float) $order->grand_total - $totalPaid);

            if ($order->balance_amount <= 0.01) {
                $order->payment_status = 'paid';
            } else {
                $order->payment_status = 'partially_paid';
            }
            $order->save();

            // If payment is cash and user has an active shift, log cash drawer transaction
            if (strtolower($method) === 'cash') {
                $activeShift = CashShift::where('user_id', $userId ?? auth()->id())
                    ->where('status', 'open')
                    ->latest()
                    ->first();

                if ($activeShift) {
                    $activeShift->cash_sales += $amount;
                    $activeShift->expected_cash += $amount;
                    $activeShift->save();

                    CashTransaction::create([
                        'cash_shift_id' => $activeShift->id,
                        'type' => 'sale',
                        'amount' => $amount,
                        'description' => "Cash payment for Order #{$order->order_number}",
                        'user_id' => $userId ?? auth()->id(),
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => 'Payment Received',
                'module' => 'Sales',
                'record_id' => $order->id,
                'new_value' => [
                    'payment_id' => $payment->id,
                    'method' => $method,
                    'amount' => $amount,
                    'new_balance' => $order->balance_amount,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $payment;
        });
    }

    /**
     * Process multiple split payments at checkout
     */
    public function processSplitPayments(Order $order, array $payments, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $payments, $userId) {
            foreach ($payments as $p) {
                $amt = (float) ($p['amount'] ?? 0);
                if ($amt > 0) {
                    $this->recordPayment(
                        $order,
                        $p['method'] ?? 'cash',
                        $amt,
                        $p['reference'] ?? null,
                        $userId
                    );
                }
            }
        });
    }
}
