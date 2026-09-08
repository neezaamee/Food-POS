<?php

namespace App\Services\Cash;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;

class CashShiftService
{
    /**
     * Get or create active shift for user
     */
    public function getActiveShift(?int $userId = null): ?CashShift
    {
        return CashShift::where('user_id', $userId ?? auth()->id())
            ->where('status', 'open')
            ->latest()
            ->first();
    }

    /**
     * Open a new cash drawer shift
     */
    public function openShift(float $openingCash = 0.00, ?string $notes = null, ?int $userId = null): CashShift
    {
        return DB::transaction(function () use ($openingCash, $notes, $userId) {
            $uId = $userId ?? auth()->id();

            $existing = CashShift::where('user_id', $uId)
                ->where('status', 'open')
                ->first();

            if ($existing) {
                throw new Exception("You already have an open cashier shift (#{$existing->id}). Please close it before opening a new one.");
            }

            $shift = CashShift::create([
                'user_id' => $uId,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'cash_sales' => 0.00,
                'cash_receipts' => 0.00,
                'cash_payments' => 0.00,
                'refunds' => 0.00,
                'expected_cash' => $openingCash,
                'status' => 'open',
                'notes' => $notes,
            ]);

            if ($openingCash > 0) {
                CashTransaction::create([
                    'cash_shift_id' => $shift->id,
                    'type' => 'cash_in',
                    'amount' => $openingCash,
                    'description' => 'Opening Cash Drawer Float',
                    'user_id' => $uId,
                ]);
            }

            AuditLog::create([
                'user_id' => $uId,
                'action' => 'Cash Shift Opened',
                'module' => 'Cash',
                'record_id' => $shift->id,
                'new_value' => ['opening_cash' => $openingCash],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $shift;
        });
    }

    /**
     * Record a manual cash in or cash out (drawer pay-in / pay-out)
     */
    public function addTransaction(CashShift $shift, string $type, float $amount, string $description, ?int $userId = null): CashTransaction
    {
        return DB::transaction(function () use ($shift, $type, $amount, $description, $userId) {
            if (! $shift->isOpen()) {
                throw new Exception('Cannot add transaction to a closed cash shift.');
            }

            if ($type === 'cash_in' || $type === 'receipt') {
                $shift->cash_receipts += $amount;
                $shift->expected_cash += $amount;
            } else {
                $shift->cash_payments += $amount;
                $shift->expected_cash -= $amount;
            }
            $shift->save();

            return CashTransaction::create([
                'cash_shift_id' => $shift->id,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'user_id' => $userId ?? auth()->id(),
            ]);
        });
    }

    /**
     * Close the cash drawer shift and calculate discrepancy
     */
    public function closeShift(CashShift $shift, float $actualCash, ?string $notes = null, ?int $userId = null): CashShift
    {
        return DB::transaction(function () use ($shift, $actualCash, $notes, $userId) {
            if (! $shift->isOpen()) {
                throw new Exception("Shift #{$shift->id} is already closed.");
            }

            // Guard: Shift cannot close until all active orders are paid
            $unpaidOrders = Order::where(function ($q) use ($shift) {
                $q->where('cash_shift_id', $shift->id)
                    ->orWhereNull('cash_shift_id');
            })
                ->where('payment_status', '!=', 'paid')
                ->where('order_status', '!=', 'cancelled')
                ->get();

            if ($unpaidOrders->isNotEmpty()) {
                $unpaidCount = $unpaidOrders->count();
                $examples = $unpaidOrders->take(3)->pluck('order_number')->implode(', ');
                throw new Exception("Cannot close Shift #{$shift->id}! There are {$unpaidCount} unpaid order(s) ({$examples}). All active orders must be paid and settled before closing the shift.");
            }

            // Recalculate expected cash
            $expected = (float) $shift->opening_cash + (float) $shift->cash_sales + (float) $shift->cash_receipts - (float) $shift->cash_payments - (float) $shift->refunds;
            $difference = $actualCash - $expected;

            $shift->expected_cash = $expected;
            $shift->actual_cash = $actualCash;
            $shift->difference = $difference;
            $shift->closed_at = now();
            $shift->status = 'closed';
            if ($notes) {
                $shift->notes = trim(($shift->notes ? $shift->notes."\n" : '').$notes);
            }
            $shift->save();

            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => 'Cash Shift Closed',
                'module' => 'Cash',
                'record_id' => $shift->id,
                'new_value' => [
                    'expected_cash' => $expected,
                    'actual_cash' => $actualCash,
                    'difference' => $difference,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $shift;
        });
    }
}
