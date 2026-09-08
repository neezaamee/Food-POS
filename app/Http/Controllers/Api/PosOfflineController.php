<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryArea;
use App\Models\Kot;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\SystemSetting;
use App\Models\TableSection;
use App\Services\Cash\CashShiftService;
use App\Services\Restaurant\TableService;
use App\Services\Sales\OrderService;
use App\Services\Sales\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PosOfflineController extends Controller
{
    public function __construct(
        protected OrderService $orderService,
        protected PaymentService $paymentService,
        protected TableService $tableService,
        protected CashShiftService $cashShiftService
    ) {}

    /**
     * Return catalog and system configuration for local IndexedDB caching
     */
    public function getCatalog(): JsonResponse
    {
        $products = Product::where('is_active', true)
            ->where('type', '!=', 'raw_material')
            ->whereNull('parent_id')
            ->with(['category:id,name,slug', 'unit:id,name,short_name', 'variants' => function ($q) {
                $q->where('is_active', true)
                    ->orderBy('sale_price', 'asc')
                    ->select(['id', 'parent_id', 'name', 'name_ur', 'sku', 'code', 'barcode', 'sale_price', 'cost_price']);
            }])
            ->select([
                'id', 'category_id', 'unit_id', 'name', 'name_ur', 'code', 'barcode', 'sku',
                'sale_price', 'cost_price', 'has_variants', 'prep_time_minutes', 'type',
            ])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'category_id' => $p->category_id,
                    'category_name' => $p->category?->name,
                    'name' => $p->name,
                    'name_ur' => $p->name_ur,
                    'code' => $p->code,
                    'barcode' => $p->barcode,
                    'sku' => $p->sku,
                    'sale_price' => (float) $p->sale_price,
                    'cost_price' => (float) $p->cost_price,
                    'prep_time_minutes' => $p->prep_time_minutes,
                    'has_variants' => (bool) $p->has_variants,
                    'variants' => $p->variants->map(fn ($v) => [
                        'id' => $v->id,
                        'name' => $v->name,
                        'name_ur' => $v->name_ur,
                        'code' => $v->code,
                        'barcode' => $v->barcode,
                        'sku' => $v->sku,
                        'sale_price' => (float) $v->sale_price,
                        'cost_price' => (float) $v->cost_price,
                    ])->toArray(),
                ];
            });

        $categories = Category::where('is_active', true)
            ->select(['id', 'name', 'slug', 'sort_order'])
            ->orderBy('sort_order')
            ->get();

        $sections = TableSection::with(['tables' => function ($q) {
            $q->where('is_active', true)
                ->select(['id', 'section_id', 'table_number', 'name', 'capacity', 'status']);
        }])
            ->where('is_active', true)
            ->get();

        $deliveryAreas = DeliveryArea::where('is_active', true)
            ->select(['id', 'name', 'delivery_charge', 'estimated_distance_km'])
            ->get();

        $activeShift = $this->cashShiftService->getActiveShift();

        $settings = [
            'restaurant_name' => SystemSetting::get('restaurant_name', 'Food Point'),
            'restaurant_address' => SystemSetting::get('restaurant_address', ''),
            'restaurant_phone' => SystemSetting::get('restaurant_phone', ''),
            'receipt_footer' => SystemSetting::get('receipt_footer', 'Thank you for dining with us!'),
            'tax_rate' => (float) SystemSetting::get('default_tax_rate', 0.00),
            'currency' => 'Rs.',
            'logo_url' => SystemSetting::logoUrl(),
        ];

        return response()->json([
            'success' => true,
            'timestamp' => now()->toISOString(),
            'products' => $products,
            'categories' => $categories,
            'sections' => $sections,
            'delivery_areas' => $deliveryAreas,
            'settings' => $settings,
            'active_shift' => $activeShift ? [
                'id' => $activeShift->id,
                'user_id' => $activeShift->user_id,
                'cashier_name' => $activeShift->user?->name ?? 'Cashier',
                'opened_at' => $activeShift->created_at->toISOString(),
                'opening_cash' => (float) $activeShift->opening_cash,
            ] : null,
        ]);
    }

    /**
     * Ingest batch of offline orders and synchronize to cloud database
     */
    public function syncOrders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.client_uuid' => 'required|string|max:36',
            'orders.*.order_type' => 'required|in:TAKEAWAY,DINE_IN,DELIVERY',
            'orders.*.items' => 'required|array|min:1',
            'orders.*.items.*.product_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid offline order synchronization payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        $incomingOrders = $request->input('orders', []);
        $synced = [];
        $errors = [];

        $activeShift = $this->cashShiftService->getActiveShift();

        foreach ($incomingOrders as $payload) {
            $uuid = $payload['client_uuid'];

            // 1. Idempotency Check: if order with this client_uuid already exists, return it
            $existingOrder = Order::where('client_uuid', $uuid)->first();
            if ($existingOrder) {
                $synced[] = [
                    'client_uuid' => $uuid,
                    'order_id' => $existingOrder->id,
                    'order_number' => $existingOrder->order_number,
                    'status' => 'already_synced',
                ];

                continue;
            }

            try {
                $order = DB::transaction(function () use ($payload, $uuid, $activeShift) {
                    $orderType = strtoupper($payload['order_type']);

                    // Validate Table for Dine-In
                    $tableId = $payload['table_id'] ?? null;
                    if ($orderType === 'DINE_IN' && empty($tableId)) {
                        throw new Exception('Dine-In order requires table_id');
                    }

                    // Resolve or create customer
                    $customerName = trim($payload['customer_name'] ?? '');
                    $customerPhone = trim($payload['customer_phone'] ?? '');
                    $customerAddress = trim($payload['customer_address'] ?? '');
                    $customerId = $payload['customer_id'] ?? null;

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
                            $customer = Customer::create([
                                'name' => (! empty($customerName) && ! in_array(strtolower($customerName), ['walk-in customer', 'walk-in'])) ? $customerName : 'Customer ('.$customerPhone.')',
                                'mobile' => $customerPhone,
                                'address' => $customerAddress ?: null,
                                'is_active' => true,
                            ]);
                            $customerId = $customer->id;
                        }
                    } elseif (! empty($customerName) && ! in_array(strtolower($customerName), ['walk-in customer', 'walk-in'])) {
                        $customer = Customer::firstOrCreate(
                            ['name' => $customerName],
                            ['mobile' => 'N/A', 'address' => $customerAddress ?: null, 'is_active' => true]
                        );
                        $customerId = $customer->id;
                    }

                    // Shift allocation
                    $shiftId = $payload['cash_shift_id'] ?? $activeShift?->id;

                    // Generate server order sequence while storing offline sequence
                    $orderNumber = $this->orderService->generateOrderNumber($orderType);
                    $offlineOrderNumber = $payload['offline_order_number'] ?? null;

                    // Resolve table details
                    $tableName = $payload['table_name'] ?? null;
                    if ($tableId && empty($tableName)) {
                        $tbl = RestaurantTable::find($tableId);
                        $tableName = $tbl?->name;
                    }

                    $order = Order::create([
                        'order_number' => $orderNumber,
                        'client_uuid' => $uuid,
                        'offline_order_number' => $offlineOrderNumber,
                        'is_offline' => true,
                        'synced_at' => now(),
                        'order_type' => $orderType,
                        'order_status' => 'draft',
                        'payment_status' => 'unpaid',
                        'customer_id' => $customerId,
                        'customer_name' => $customerName ?: 'Walk-in Customer',
                        'customer_phone' => $customerPhone ?: null,
                        'customer_address' => $customerAddress ?: null,
                        'table_id' => $tableId,
                        'table_name' => $tableName,
                        'delivery_area_id' => $payload['delivery_area_id'] ?? null,
                        'delivery_rider_id' => $payload['delivery_rider_id'] ?? null,
                        'delivery_charge' => (float) ($payload['delivery_charge'] ?? 0.00),
                        'subtotal' => (float) ($payload['subtotal'] ?? 0.00),
                        'discount_type' => $payload['discount_type'] ?? 'fixed',
                        'discount_rate' => (float) ($payload['discount_rate'] ?? 0.00),
                        'discount_amount' => (float) ($payload['discount_amount'] ?? 0.00),
                        'tax_amount' => (float) ($payload['tax_amount'] ?? 0.00),
                        'grand_total' => (float) ($payload['grand_total'] ?? 0.00),
                        'notes' => trim(($payload['notes'] ?? '').($offlineOrderNumber ? " [Offline: {$offlineOrderNumber}]" : '')),
                        'user_id' => auth()->id() ?? $payload['user_id'] ?? 1,
                        'cash_shift_id' => $shiftId,
                        'created_at' => ! empty($payload['created_at']) ? $payload['created_at'] : now(),
                    ]);

                    // Create items
                    foreach ($payload['items'] as $item) {
                        $itemQty = (float) ($item['quantity'] ?? $item['qty'] ?? 1);
                        $itemPrice = (float) ($item['unit_price'] ?? $item['price'] ?? 0.00);
                        $itemCost = (float) ($item['unit_cost'] ?? $item['cost'] ?? 0.00);

                        $order->items()->create([
                            'product_id' => $item['product_id'],
                            'product_name' => $item['product_name'] ?? $item['name'] ?? 'Item',
                            'product_name_ur' => $item['product_name_ur'] ?? $item['name_ur'] ?? null,
                            'product_sku' => $item['product_sku'] ?? $item['sku'] ?? null,
                            'quantity' => $itemQty,
                            'unit_cost' => $itemCost,
                            'unit_price' => $itemPrice,
                            'discount_amount' => 0.00,
                            'tax_amount' => 0.00,
                            'subtotal' => (float) ($itemQty * $itemPrice),
                            'notes' => $item['notes'] ?? null,
                            'status' => 'pending',
                        ]);
                    }

                    $this->orderService->recalculateOrder($order);

                    // Generate KOT Ticket
                    $kot = Kot::create([
                        'order_id' => $order->id,
                        'kot_number' => 1,
                        'status' => 'prep',
                        'user_id' => $order->user_id,
                        'notes' => 'Generated from offline POS order',
                    ]);

                    foreach ($order->items as $oItem) {
                        $kot->items()->create([
                            'product_id' => $oItem->product_id,
                            'product_name' => $oItem->product_name,
                            'product_name_ur' => $oItem->product_name_ur,
                            'quantity' => $oItem->quantity,
                            'notes' => $oItem->notes,
                            'status' => 'prep',
                        ]);
                    }

                    // Process Payment & Finalization if marked completed offline
                    $isFinalizedOffline = ! empty($payload['is_finalized']) || (! empty($payload['payment_status']) && $payload['payment_status'] === 'paid');
                    if ($isFinalizedOffline) {
                        $paymentMethod = $payload['payment_method'] ?? 'cash';
                        $paidAmount = (float) ($payload['paid_amount'] ?? $order->grand_total);
                        $this->paymentService->recordPayment(
                            $order,
                            $paymentMethod,
                            min($order->grand_total, $paidAmount),
                            $payload['payment_reference'] ?? 'Offline Sync'
                        );

                        // Finalize order (deducts stock, recipe raw materials, posts accounting)
                        $this->orderService->finalizeOrder($order);
                    } elseif ($orderType === 'DINE_IN' && $order->table_id) {
                        // Keep table occupied for open draft
                        $table = RestaurantTable::find($order->table_id);
                        if ($table) {
                            $this->tableService->occupyTable($table, $order);
                        }
                    }

                    AuditLog::create([
                        'user_id' => $order->user_id,
                        'action' => 'Offline Order Synced',
                        'module' => 'POS',
                        'record_id' => $order->id,
                        'new_value' => [
                            'client_uuid' => $uuid,
                            'offline_order_number' => $offlineOrderNumber,
                            'server_order_number' => $order->order_number,
                            'grand_total' => $order->grand_total,
                        ],
                    ]);

                    return $order;
                });

                $synced[] = [
                    'client_uuid' => $uuid,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'offline_order_number' => $order->offline_order_number,
                    'status' => 'synced',
                ];
            } catch (Exception $e) {
                $errors[] = [
                    'client_uuid' => $uuid,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'synced_count' => count($synced),
            'synced' => $synced,
            'errors' => $errors,
        ]);
    }
}
