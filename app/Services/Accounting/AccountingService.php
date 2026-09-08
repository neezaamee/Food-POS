<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\SaleReturn;
use Exception;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Create a double-entry journal entry.
     * Enforces: SUM(debits) === SUM(credits).
     */
    public function createEntry(array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($data, $lines) {
            $totalDebit = 0.00;
            $totalCredit = 0.00;

            foreach ($lines as $line) {
                $totalDebit += (float) ($line['debit'] ?? 0.00);
                $totalCredit += (float) ($line['credit'] ?? 0.00);
            }

            // Enforce double entry rule
            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new Exception("Double Entry Accounting Mismatch! Total Debit ({$totalDebit}) does not equal Total Credit ({$totalCredit}).");
            }

            $entryNumber = $data['entry_number'] ?? $this->generateEntryNumber($data['voucher_type'] ?? 'journal');

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'voucher_type' => $data['voucher_type'] ?? 'journal',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            foreach ($lines as $line) {
                $account = Account::findOrFail($line['account_id']);
                $debit = (float) ($line['debit'] ?? 0.00);
                $credit = (float) ($line['credit'] ?? 0.00);

                $entry->lines()->create([
                    'account_id' => $account->id,
                    'debit' => $debit,
                    'credit' => $credit,
                    'description' => $line['description'] ?? null,
                ]);

                // Update account balance based on nature
                if ($account->debit_credit_nature === 'debit') {
                    $account->current_balance += ($debit - $credit);
                } else {
                    $account->current_balance += ($credit - $debit);
                }
                $account->save();
            }

            return $entry;
        });
    }

    /**
     * Post finalized Order to Double-Entry Accounting
     */
    public function postOrderSale(Order $order): JournalEntry
    {
        $cashAcc = Account::where('code', '1110')->firstOrFail();
        $bankAcc = Account::where('code', '1120')->firstOrFail();
        $arAcc = Account::where('code', '1130')->firstOrFail();
        $salesAcc = Account::where('code', '4110')->firstOrFail();
        $deliveryAcc = Account::where('code', '4120')->firstOrFail();
        $taxAcc = Account::where('code', '2110')->firstOrFail();
        $cogsAcc = Account::where('code', '5110')->firstOrFail();
        $invAcc = Account::where('code', '1140')->firstOrFail();

        $lines = [];

        // 1. Debits from Payments
        $totalPaid = 0.00;
        foreach ($order->payments as $payment) {
            $amt = (float) $payment->amount;
            if ($amt <= 0) {
                continue;
            }

            $totalPaid += $amt;
            $destAcc = ($payment->payment_method === 'bank' || $payment->payment_method === 'card' || $payment->payment_method === 'digital')
                ? $bankAcc->id
                : $cashAcc->id;

            $lines[] = [
                'account_id' => $destAcc,
                'debit' => $amt,
                'credit' => 0.00,
                'description' => 'Payment via '.ucfirst($payment->payment_method)." for Order #{$order->order_number}",
            ];
        }

        // 2. Debit Accounts Receivable for unpaid balance
        $balance = (float) $order->balance_amount;
        if ($balance > 0.01) {
            $lines[] = [
                'account_id' => $arAcc->id,
                'debit' => $balance,
                'credit' => 0.00,
                'description' => "Credit Receivable for Order #{$order->order_number} (".($order->customer_name ?? 'Guest').')',
            ];
        }

        // 3. Credit Food Sales (net of discount, excluding delivery and tax)
        $foodSales = (float) $order->subtotal - (float) $order->discount_amount;
        if ($foodSales > 0) {
            $lines[] = [
                'account_id' => $salesAcc->id,
                'debit' => 0.00,
                'credit' => $foodSales,
                'description' => "Food Sales Revenue for Order #{$order->order_number}",
            ];
        }

        // 4. Credit Delivery Fee if any
        $deliveryFee = (float) $order->delivery_charge;
        if ($deliveryFee > 0) {
            $lines[] = [
                'account_id' => $deliveryAcc->id,
                'debit' => 0.00,
                'credit' => $deliveryFee,
                'description' => "Delivery Charges Income for Order #{$order->order_number}",
            ];
        }

        // 5. Credit Tax Payable if any
        $tax = (float) $order->tax_amount;
        if ($tax > 0) {
            $lines[] = [
                'account_id' => $taxAcc->id,
                'debit' => 0.00,
                'credit' => $tax,
                'description' => "Sales Tax on Order #{$order->order_number}",
            ];
        }

        // 6. Cost of Goods Sold & Inventory Asset (balanced pair)
        $totalCost = 0.00;
        foreach ($order->items as $item) {
            $totalCost += ((float) $item->unit_cost * (float) $item->quantity);
        }

        if ($totalCost > 0) {
            $lines[] = [
                'account_id' => $cogsAcc->id,
                'debit' => $totalCost,
                'credit' => 0.00,
                'description' => "Cost of Goods Sold for Order #{$order->order_number}",
            ];
            $lines[] = [
                'account_id' => $invAcc->id,
                'debit' => 0.00,
                'credit' => $totalCost,
                'description' => "Inventory reduction for Order #{$order->order_number}",
            ];
        }

        return $this->createEntry([
            'voucher_type' => 'sale',
            'entry_date' => now()->toDateString(),
            'reference_type' => 'Order',
            'reference_id' => $order->id,
            'notes' => "Automated Sales Posting for Order #{$order->order_number} ({$order->order_type})",
            'user_id' => $order->user_id,
        ], $lines);
    }

    /**
     * Post Sale Return reversal to Accounting
     */
    public function postSaleReturn(SaleReturn $return): JournalEntry
    {
        $cashAcc = Account::where('code', '1110')->firstOrFail();
        $bankAcc = Account::where('code', '1120')->firstOrFail();
        $arAcc = Account::where('code', '1130')->firstOrFail();
        $salesAcc = Account::where('code', '4110')->firstOrFail();
        $cogsAcc = Account::where('code', '5110')->firstOrFail();
        $invAcc = Account::where('code', '1140')->firstOrFail();

        $lines = [];
        $grandTotal = (float) $return->grand_total;

        // Debit Sales (Sales Reversal)
        $lines[] = [
            'account_id' => $salesAcc->id,
            'debit' => $grandTotal,
            'credit' => 0.00,
            'description' => "Sale Return Reversal for Return #{$return->return_number}",
        ];

        // Credit Cash / Bank / Customer AR (Refund Outflow or balance reduction)
        $creditAccId = match ($return->refund_method) {
            'bank' => $bankAcc->id,
            'credit' => $arAcc->id,
            default => $cashAcc->id,
        };

        $lines[] = [
            'account_id' => $creditAccId,
            'debit' => 0.00,
            'credit' => $grandTotal,
            'description' => 'Refund paid via '.ucfirst($return->refund_method)." for Return #{$return->return_number}",
        ];

        // Inventory Restock & COGS Reversal (Debit Inventory, Credit COGS)
        $returnCost = 0.00;
        foreach ($return->items as $rItem) {
            $cost = (float) ($rItem->product->cost_price ?? 0.00);
            $returnCost += ($cost * (float) $rItem->quantity);
        }

        if ($returnCost > 0) {
            $lines[] = [
                'account_id' => $invAcc->id,
                'debit' => $returnCost,
                'credit' => 0.00,
                'description' => "Inventory restocked from Return #{$return->return_number}",
            ];
            $lines[] = [
                'account_id' => $cogsAcc->id,
                'debit' => 0.00,
                'credit' => $returnCost,
                'description' => "COGS reversal for Return #{$return->return_number}",
            ];
        }

        return $this->createEntry([
            'voucher_type' => 'sale_return',
            'entry_date' => now()->toDateString(),
            'reference_type' => 'SaleReturn',
            'reference_id' => $return->id,
            'notes' => "Automated Sale Return Posting for Return #{$return->return_number} (Reason: {$return->reason})",
            'user_id' => $return->user_id,
        ], $lines);
    }

    /**
     * Reverse finalized Order sale from Double-Entry Accounting
     */
    public function reverseOrderSale(Order $order, string $reason = '', bool $isWaste = false): JournalEntry
    {
        $cashAcc = Account::where('code', '1110')->firstOrFail();
        $bankAcc = Account::where('code', '1120')->firstOrFail();
        $arAcc = Account::where('code', '1130')->firstOrFail();
        $salesAcc = Account::where('code', '4110')->firstOrFail();
        $deliveryAcc = Account::where('code', '4120')->firstOrFail();
        $taxAcc = Account::where('code', '2110')->firstOrFail();
        $cogsAcc = Account::where('code', '5110')->firstOrFail();
        $invAcc = Account::where('code', '1140')->firstOrFail();

        $lines = [];

        // 1. Debit Food Sales to reverse revenue
        $foodSales = (float) $order->subtotal - (float) $order->discount_amount;
        if ($foodSales > 0) {
            $lines[] = [
                'account_id' => $salesAcc->id,
                'debit' => $foodSales,
                'credit' => 0.00,
                'description' => "Sales Revenue Reversal for Cancelled Order #{$order->order_number}",
            ];
        }

        // 2. Debit Delivery Charges if any
        $deliveryFee = (float) $order->delivery_charge;
        if ($deliveryFee > 0) {
            $lines[] = [
                'account_id' => $deliveryAcc->id,
                'debit' => $deliveryFee,
                'credit' => 0.00,
                'description' => "Delivery Charges Reversal for Cancelled Order #{$order->order_number}",
            ];
        }

        // 3. Debit Tax Payable if any
        $tax = (float) $order->tax_amount;
        if ($tax > 0) {
            $lines[] = [
                'account_id' => $taxAcc->id,
                'debit' => $tax,
                'credit' => 0.00,
                'description' => "Sales Tax Reversal for Cancelled Order #{$order->order_number}",
            ];
        }

        // 4. Credit Payments (Cash / Bank)
        foreach ($order->payments as $payment) {
            $amt = (float) $payment->amount;
            if ($amt <= 0) {
                continue;
            }

            $destAcc = ($payment->payment_method === 'bank' || $payment->payment_method === 'card' || $payment->payment_method === 'digital')
                ? $bankAcc->id
                : $cashAcc->id;

            $lines[] = [
                'account_id' => $destAcc,
                'debit' => 0.00,
                'credit' => $amt,
                'description' => 'Payment Refund ('.ucfirst($payment->payment_method).") for Cancelled Order #{$order->order_number}",
            ];
        }

        // 5. Credit AR for unpaid portion
        $balance = (float) $order->balance_amount;
        if ($balance > 0.01) {
            $lines[] = [
                'account_id' => $arAcc->id,
                'debit' => 0.00,
                'credit' => $balance,
                'description' => "Receivable Reversal for Cancelled Order #{$order->order_number}",
            ];
        }

        // 6. Inventory & COGS Reversal (only if items are restocked, not discarded as waste)
        if (! $isWaste) {
            $totalCost = 0.00;
            foreach ($order->items as $item) {
                $totalCost += ((float) $item->unit_cost * (float) $item->quantity);
            }

            if ($totalCost > 0) {
                $lines[] = [
                    'account_id' => $invAcc->id,
                    'debit' => $totalCost,
                    'credit' => 0.00,
                    'description' => "Inventory Restocked from Cancelled Order #{$order->order_number}",
                ];
                $lines[] = [
                    'account_id' => $cogsAcc->id,
                    'debit' => 0.00,
                    'credit' => $totalCost,
                    'description' => "COGS Reversal for Cancelled Order #{$order->order_number}",
                ];
            }
        }

        return $this->createEntry([
            'voucher_type' => 'sale_return',
            'entry_date' => now()->toDateString(),
            'reference_type' => 'Order',
            'reference_id' => $order->id,
            'notes' => "Order Cancellation Reversal for Order #{$order->order_number}. Reason: {$reason}",
            'user_id' => auth()->id() ?? $order->user_id,
        ], $lines);
    }

    private function generateEntryNumber(string $voucherType): string
    {
        $prefix = match ($voucherType) {
            'sale' => 'SL',
            'sale_return' => 'SR',
            'receipt' => 'CR',
            'payment' => 'CP',
            default => 'JV',
        };

        $year = date('Y');
        $count = JournalEntry::where('entry_number', 'LIKE', "{$prefix}-{$year}%")->count() + 1;

        return sprintf('%s-%s%05d', $prefix, $year, $count);
    }
}
