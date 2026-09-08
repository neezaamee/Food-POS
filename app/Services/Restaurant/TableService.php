<?php

namespace App\Services\Restaurant;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\RestaurantTable;
use Exception;
use Illuminate\Support\Facades\DB;

class TableService
{
    /**
     * Occupy a table with an active order
     */
    public function occupyTable(RestaurantTable $table, Order $order): void
    {
        $table->status = 'occupied';
        $table->active_order_id = $order->id;
        $table->save();

        $order->table_id = $table->id;
        $order->table_name = $table->name;
        $order->save();
    }

    /**
     * Release a table back to available
     */
    public function releaseTable(RestaurantTable $table): void
    {
        $table->status = 'available';
        $table->active_order_id = null;
        $table->save();
    }

    /**
     * Transfer an open Dine-In order from one table to another
     */
    public function transferTable(Order $order, RestaurantTable $toTable, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $toTable, $userId) {
            $fromTable = $order->table;

            if (! $fromTable) {
                throw new Exception('The order is not currently assigned to any table.');
            }

            if ($fromTable->id === $toTable->id) {
                throw new Exception('Source and destination table cannot be the same.');
            }

            if (! $toTable->isAvailable() && $toTable->active_order_id !== null) {
                throw new Exception("Target Table '{$toTable->name}' is currently occupied.");
            }

            // 1. Release Source Table
            $fromTable->status = 'available';
            $fromTable->active_order_id = null;
            $fromTable->save();

            // 2. Occupy Target Table
            $toTable->status = 'occupied';
            $toTable->active_order_id = $order->id;
            $toTable->save();

            // 3. Update Order Reference
            $oldTableName = $order->table_name;
            $order->table_id = $toTable->id;
            $order->table_name = $toTable->name;
            $order->save();

            // 4. Audit Log
            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => 'Table Transfer',
                'module' => 'Restaurant',
                'record_id' => $order->id,
                'old_value' => ['table_id' => $fromTable->id, 'table_name' => $oldTableName],
                'new_value' => ['table_id' => $toTable->id, 'table_name' => $toTable->name],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Merge items from source order/table into destination order/table
     */
    public function mergeTables(Order $sourceOrder, Order $destOrder, ?int $userId = null): void
    {
        DB::transaction(function () use ($sourceOrder, $destOrder, $userId) {
            $sourceTable = $sourceOrder->table;

            // Move all order items from source to destination
            foreach ($sourceOrder->items as $item) {
                $destOrder->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'tax_amount' => $item->tax_amount,
                    'subtotal' => $item->subtotal,
                    'notes' => $item->notes." (Merged from Table {$sourceTable?->name})",
                    'status' => $item->status,
                ]);
            }

            // Recalculate destination order totals
            $newSubtotal = $destOrder->items()->sum('subtotal');
            $destOrder->subtotal = $newSubtotal;
            $destOrder->grand_total = $newSubtotal - $destOrder->discount_amount + $destOrder->tax_amount + $destOrder->delivery_charge;
            $destOrder->balance_amount = $destOrder->grand_total - $destOrder->paid_amount;
            $destOrder->notes .= " [Merged items from Order #{$sourceOrder->order_number}]";
            $destOrder->save();

            // Cancel source order
            $sourceOrder->order_status = 'cancelled';
            $sourceOrder->notes .= " [Merged into Order #{$destOrder->order_number}]";
            $sourceOrder->save();

            // Release source table
            if ($sourceTable) {
                $sourceTable->status = 'available';
                $sourceTable->active_order_id = null;
                $sourceTable->save();
            }

            // Audit
            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => 'Table Merge',
                'module' => 'Restaurant',
                'record_id' => $destOrder->id,
                'old_value' => ['merged_from_order_id' => $sourceOrder->id, 'merged_from_table' => $sourceTable?->name],
                'new_value' => ['destination_order_id' => $destOrder->id, 'new_grand_total' => $destOrder->grand_total],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
