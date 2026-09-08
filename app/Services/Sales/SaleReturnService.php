<?php

namespace App\Services\Sales;

use App\Models\AuditLog;
use App\Models\CashShift;
use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\SaleReturn;
use App\Services\Accounting\AccountingService;
use App\Services\Inventory\StockService;
use Exception;
use Illuminate\Support\Facades\DB;

class SaleReturnService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected StockService $stockService,
    ) {}

    /**
     * Create a sale return / credit note
     */
    public function createReturn(Order $order, array $items, string $reason, string $refundMethod = 'cash', ?int $userId = null): SaleReturn
    {
        return DB::transaction(function () use ($order, $items, $reason, $refundMethod, $userId) {
            if (! $order->isFinalized()) {
                throw new Exception('Cannot process a return for an order that has not been finalized.');
            }

            $subtotal = 0.00;
            $returnItemsData = [];

            foreach ($items as $itemData) {
                $orderItemId = $itemData['order_item_id'];
                $returnQty = (float) $itemData['quantity'];

                if ($returnQty <= 0) {
                    continue;
                }

                $orderItem = $order->items()->findOrFail($orderItemId);

                // Check already returned quantity
                $alreadyReturned = (float) DB::table('sale_return_items')
                    ->where('order_item_id', $orderItem->id)
                    ->sum('quantity');

                $maxReturnable = (float) $orderItem->quantity - $alreadyReturned;

                if ($returnQty > $maxReturnable) {
                    throw new Exception("Return quantity ({$returnQty}) for '{$orderItem->product_name}' exceeds remaining sold quantity ({$maxReturnable}).");
                }

                $itemSubtotal = $returnQty * (float) $orderItem->unit_price;
                $subtotal += $itemSubtotal;

                $returnItemsData[] = [
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'quantity' => $returnQty,
                    'unit_price' => $orderItem->unit_price,
                    'subtotal' => $itemSubtotal,
                ];
            }

            if (empty($returnItemsData)) {
                throw new Exception('No valid items specified for return.');
            }

            $year = date('Y');
            $count = SaleReturn::whereYear('created_at', $year)->count() + 1;
            $returnNumber = sprintf('RET-%s%06d', $year, $count);

            $saleReturn = SaleReturn::create([
                'return_number' => $returnNumber,
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'subtotal' => $subtotal,
                'tax_amount' => 0.00,
                'grand_total' => $subtotal,
                'refund_method' => $refundMethod,
                'reason' => $reason,
                'user_id' => $userId ?? auth()->id(),
            ]);

            foreach ($returnItemsData as $rItem) {
                $saleReturn->items()->create($rItem);
            }

            // 1. Restock items via StockService
            $this->stockService->restockReturnItems($saleReturn);

            // 2. Post Accounting Reversal via AccountingService
            $this->accountingService->postSaleReturn($saleReturn);

            // 3. If customer account, adjust balance if credit refund
            if ($order->customer && $refundMethod === 'credit') {
                $order->customer->current_balance = max(0, $order->customer->current_balance - $subtotal);
                $order->customer->save();
            }

            // 4. If cash refund, deduct from active cash shift
            if ($refundMethod === 'cash') {
                $activeShift = CashShift::where('user_id', $userId ?? auth()->id())
                    ->where('status', 'open')
                    ->latest()
                    ->first();

                if ($activeShift) {
                    $activeShift->refunds += $subtotal;
                    $activeShift->expected_cash -= $subtotal;
                    $activeShift->save();

                    CashTransaction::create([
                        'cash_shift_id' => $activeShift->id,
                        'type' => 'refund',
                        'amount' => $subtotal,
                        'description' => "Refund for Return #{$saleReturn->return_number} (Order #{$order->order_number})",
                        'user_id' => $userId ?? auth()->id(),
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => 'Sale Return Created',
                'module' => 'Sales',
                'record_id' => $saleReturn->id,
                'new_value' => [
                    'return_number' => $saleReturn->return_number,
                    'order_id' => $order->id,
                    'grand_total' => $subtotal,
                    'reason' => $reason,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $saleReturn;
        });
    }
}
