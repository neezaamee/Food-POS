<?php

namespace App\Services\Sales;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Inventory\StockService;
use App\Services\Restaurant\TableService;
use App\Services\SaaS\SubscriptionService;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected AccountingService $accountingService,
        protected StockService $stockService,
        protected TableService $tableService,
    ) {}

    /**
     * Generate unique sequential order number based on type
     */
    public function generateOrderNumber(string $orderType): string
    {
        $normalizedType = strtoupper($orderType);
        $prefix = match ($normalizedType) {
            'DINE_IN' => 'DIN',
            'DELIVERY' => 'DEL',
            default => 'TAK',
        };

        $year = date('Y');
        $count = Order::withTrashed()
            ->where('order_type', $normalizedType)
            ->whereYear('created_at', $year)
            ->count() + 1;

        do {
            $candidate = sprintf('%s-%s%06d', $prefix, $year, $count);
            $count++;
        } while (Order::withTrashed()->where('order_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Create a new draft order
     */
    public function createOrder(array $data): Order
    {
        if (! app(SubscriptionService::class)->canCreateOrder()) {
            throw new Exception('Monthly order limit reached for your subscription plan. Please upgrade your plan to continue processing orders.');
        }

        return DB::transaction(function () use ($data) {
            $orderType = strtoupper($data['order_type'] ?? 'TAKEAWAY');
            if ($orderType === 'DINE_IN' && empty($data['table_id'])) {
                throw new Exception('Table selection is mandatory for Dine-In orders. Please select a table.');
            }
            $orderNumber = $this->generateOrderNumber($orderType);

            // Auto-resolve or create customer profile if customer details provided
            $customerId = $data['customer_id'] ?? null;
            $customerPhone = trim($data['customer_phone'] ?? '');
            $customerName = trim($data['customer_name'] ?? '');
            $customerAddress = trim($data['customer_address'] ?? '');

            if (! $customerId) {
                if (! empty($customerPhone)) {
                    $customer = Customer::where('mobile', $customerPhone)->first();
                    if ($customer) {
                        if (! empty($customerName) && ! in_array(strtolower($customerName), ['walk-in customer', 'walk-in'])) {
                            $customer->name = $customerName;
                        }
                        if (! empty($customerAddress)) {
                            $customer->address = $customerAddress;
                        }
                        $customer->save();
                        $customerId = $customer->id;
                    } else {
                        $newCustomer = Customer::create([
                            'name' => (! empty($customerName) && ! in_array(strtolower($customerName), ['walk-in customer', 'walk-in'])) ? $customerName : 'Customer ('.$customerPhone.')',
                            'mobile' => $customerPhone,
                            'address' => $customerAddress ?: null,
                            'is_active' => true,
                        ]);
                        $customerId = $newCustomer->id;
                    }
                } elseif (! empty($customerName) && ! in_array(strtolower($customerName), ['walk-in customer', 'walk-in'])) {
                    $customer = Customer::firstOrCreate(
                        ['name' => $customerName],
                        [
                            'mobile' => 'N/A',
                            'address' => $customerAddress ?: null,
                            'is_active' => true,
                        ]
                    );
                    $customerId = $customer->id;
                }
            }

            $order = Order::create([
                'order_number' => $orderNumber,
                'order_type' => $orderType,
                'order_status' => 'draft',
                'payment_status' => 'unpaid',
                'customer_id' => $customerId,
                'customer_name' => $customerName ?: 'Walk-in Customer',
                'customer_phone' => $customerPhone ?: null,
                'customer_address' => $customerAddress ?: null,
                'table_id' => $data['table_id'] ?? null,
                'table_name' => $data['table_name'] ?? null,
                'delivery_area_id' => $data['delivery_area_id'] ?? null,
                'delivery_rider_id' => $data['delivery_rider_id'] ?? null,
                'delivery_charge' => $data['delivery_charge'] ?? 0.00,
                'delivery_distance_km' => $data['delivery_distance_km'] ?? 0.00,
                'subtotal' => 0.00,
                'discount_type' => $data['discount_type'] ?? 'fixed',
                'discount_rate' => $data['discount_rate'] ?? 0.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'grand_total' => 0.00,
                'paid_amount' => 0.00,
                'balance_amount' => 0.00,
                'notes' => $data['notes'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'cash_shift_id' => $data['cash_shift_id'] ?? null,
            ]);

            // If dine-in with table selected, occupy the table
            if ($orderType === 'DINE_IN' && ! empty($data['table_id'])) {
                $table = RestaurantTable::findOrFail($data['table_id']);
                $this->tableService->occupyTable($table, $order);
            }

            AuditLog::create([
                'user_id' => $order->user_id,
                'action' => 'Order Created',
                'module' => 'Sales',
                'record_id' => $order->id,
                'new_value' => ['order_number' => $order->order_number, 'order_type' => $order->order_type],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $order;
        });
    }

    /**
     * Add or update an item in the order
     */
    public function addItem(Order $order, Product $product, float $quantity = 1, ?string $notes = null): OrderItem
    {
        return DB::transaction(function () use ($order, $product, $quantity, $notes) {
            $item = $order->items()->where('product_id', $product->id)->first();

            if ($item) {
                $item->quantity += $quantity;
                if (! empty($notes)) {
                    $item->notes = trim(($item->notes ? $item->notes.'; ' : '').$notes);
                }
                $item->subtotal = (float) $item->quantity * (float) $item->unit_price;
                $item->save();
            } else {
                $subtotal = $quantity * (float) $product->sale_price;
                $item = $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_cost' => $product->cost_price,
                    'unit_price' => $product->sale_price,
                    'discount_amount' => 0.00,
                    'tax_amount' => 0.00,
                    'subtotal' => $subtotal,
                    'notes' => $notes,
                    'status' => 'pending',
                ]);
            }

            $this->recalculateOrder($order);

            return $item;
        });
    }

    /**
     * Update item quantity
     */
    public function updateItemQuantity(OrderItem $item, float $newQuantity): void
    {
        DB::transaction(function () use ($item, $newQuantity) {
            if ($newQuantity <= 0) {
                $order = $item->order;
                $item->delete();
                $this->recalculateOrder($order);

                return;
            }

            $item->quantity = $newQuantity;
            $item->subtotal = $newQuantity * (float) $item->unit_price;
            $item->save();

            $this->recalculateOrder($item->order);
        });
    }

    /**
     * Remove item from order
     */
    public function removeItem(OrderItem $item): void
    {
        DB::transaction(function () use ($item) {
            $order = $item->order;
            $item->delete();
            $this->recalculateOrder($order);
        });
    }

    /**
     * Recalculate order totals (subtotal, discounts, taxes, delivery, grand total, balance)
     */
    public function recalculateOrder(Order $order): void
    {
        $subtotal = (float) $order->items()->sum('subtotal');
        $order->subtotal = $subtotal;

        // Calculate discount
        $discountAmount = 0.00;
        if ($order->discount_type === 'percent') {
            $discountAmount = ($subtotal * (float) $order->discount_rate) / 100.0;
        } else {
            $discountAmount = min($subtotal, (float) $order->discount_rate);
        }
        $order->discount_amount = $discountAmount;

        // Calculate grand total
        $grandTotal = $subtotal - $discountAmount + (float) $order->tax_amount + (float) $order->delivery_charge;
        $order->grand_total = max(0, $grandTotal);

        // Calculate balance
        $paid = (float) $order->payments()->sum('amount');
        $order->paid_amount = $paid;
        $order->balance_amount = max(0, $order->grand_total - $paid);

        // Update payment status
        if ($paid <= 0) {
            $order->payment_status = 'unpaid';
        } elseif ($order->balance_amount <= 0.01) {
            $order->payment_status = 'paid';
        } else {
            $order->payment_status = 'partially_paid';
        }

        $order->save();
    }

    /**
     * Finalize Order:
     * - Posts to Double-Entry Accounting
     * - Posts Stock Deduction OUT
     * - Releases Table if Dine-In
     * - Completes order status
     */
    public function finalizeOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->isFinalized()) {
                throw new Exception("Order #{$order->order_number} has already been finalized.");
            }

            if ($order->items()->count() === 0) {
                throw new Exception('Cannot finalize an empty order with no items.');
            }

            $this->recalculateOrder($order);

            // 1. Post Inventory OUT
            $this->stockService->deductOrderStock($order);

            // 2. Post Accounting Double-Entry
            $this->accountingService->postOrderSale($order);

            // 3. Update Customer Outstanding Balance if Credit/Partial
            if ($order->customer && $order->balance_amount > 0.01) {
                $order->customer->current_balance += $order->balance_amount;
                $order->customer->save();
            }

            // 4. Release Dine-In Table
            if ($order->order_type === 'DINE_IN' && $order->table) {
                $this->tableService->releaseTable($order->table);
            }

            // 5. Finalize status and timestamp
            $order->finalized_at = now();
            $order->order_status = 'completed';
            $order->save();

            AuditLog::create([
                'user_id' => auth()->id() ?? $order->user_id,
                'action' => 'Order Finalized',
                'module' => 'Sales',
                'record_id' => $order->id,
                'new_value' => [
                    'order_number' => $order->order_number,
                    'grand_total' => $order->grand_total,
                    'paid_amount' => $order->paid_amount,
                    'balance_amount' => $order->balance_amount,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Cancel an order (both Draft and Completed/Finalized):
     * - Enforces Role Authorization (Manager, Owner, Super Admin only).
     * - Enforces Shift Closure Rule: Cannot cancel if the shift is closed.
     * - If finalized: reverses inventory (restock or waste), accounting, and shift cash.
     * - Releases table if dine-in.
     * - Updates status to 'cancelled'.
     */
    public function cancelOrder(Order $order, string $reason, ?User $user = null, bool $isWaste = false): void
    {
        $actor = $user ?? auth()->user();

        // 1. Authorization: Only Manager, Owner, or Super Administrator can cancel
        if ($actor && ! $actor->canCancelOrder()) {
            throw new Exception('Unauthorized! Only a Manager, Owner, or Super Administrator has the authority to cancel an order.');
        }

        // 2. Shift Closure Rule: Cannot cancel if shift is closed
        if ($order->cashShift && ! $order->cashShift->isOpen()) {
            throw new Exception("Order #{$order->order_number} belongs to Shift #{$order->cash_shift_id} which has already been closed. Orders cannot be cancelled after the shift is closed.");
        }

        if ($order->order_status === 'cancelled') {
            throw new Exception("Order #{$order->order_number} is already cancelled.");
        }

        DB::transaction(function () use ($order, $reason, $actor, $isWaste) {
            $wasFinalized = $order->isFinalized();

            if ($wasFinalized) {
                // A. Reverse Inventory
                if (! $isWaste) {
                    $this->stockService->restockCancelledOrder($order, "Restocked from cancelled Order #{$order->order_number} by ".($actor->name ?? 'Staff'));
                } else {
                    $this->stockService->recordOrderWaste($order, "Discarded as waste/spoilage on cancellation of Order #{$order->order_number}");
                }

                // B. Reverse Accounting Double-Entry
                $this->accountingService->reverseOrderSale($order, "Cancelled Order #{$order->order_number} (Reason: {$reason})", $isWaste);

                // C. Adjust Cash Shift if cash was received
                if ($order->cashShift && $order->cashShift->isOpen()) {
                    $cashPaid = (float) $order->payments()->where('payment_method', 'cash')->sum('amount');
                    if ($cashPaid > 0) {
                        $order->cashShift->cash_sales = max(0, (float) $order->cashShift->cash_sales - $cashPaid);
                        $order->cashShift->expected_cash = max(0, (float) $order->cashShift->expected_cash - $cashPaid);
                        $order->cashShift->save();

                        $order->cashShift->transactions()->create([
                            'type' => 'refund',
                            'amount' => $cashPaid,
                            'description' => "Cash drawer reversal for cancelled Order #{$order->order_number}",
                            'user_id' => $actor?->id,
                        ]);
                    }
                }

                // D. Customer AR adjustment if credit
                if ($order->customer && $order->balance_amount > 0.01) {
                    $order->customer->current_balance = max(0, (float) $order->customer->current_balance - (float) $order->balance_amount);
                    $order->customer->save();
                }
            }

            // Release Dining Table
            if ($order->table) {
                $this->tableService->releaseTable($order->table);
            }

            // Update Order Status
            $order->order_status = 'cancelled';
            $order->notes = trim(($order->notes ? $order->notes.' | ' : '').'Cancelled by '.($actor->name ?? 'User').": {$reason}".($wasFinalized ? ($isWaste ? ' (Food Wasted)' : ' (Restocked)') : ''));
            $order->save();

            AuditLog::create([
                'user_id' => $actor?->id ?? $order->user_id,
                'action' => 'Order Cancelled',
                'module' => 'Sales',
                'record_id' => $order->id,
                'new_value' => [
                    'order_number' => $order->order_number,
                    'was_finalized' => $wasFinalized,
                    'reason' => $reason,
                    'is_waste' => $isWaste,
                    'cancelled_by' => $actor?->name,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }
}
