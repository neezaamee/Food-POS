<?php

namespace App\Livewire\Pos;

use App\Models\CashShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\DeliveryArea;
use App\Models\DeliveryRider;
use App\Models\Kot;
use App\Models\KotItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\TableSection;
use App\Services\Cash\CashShiftService;
use App\Services\Restaurant\TableService;
use App\Services\Sales\OrderService;
use App\Services\Sales\PaymentService;
use Exception;
use Livewire\Component;

class PosScreen extends Component
{
    // Order Type: TAKEAWAY, DINE_IN, DELIVERY
    public string $orderType = 'TAKEAWAY';

    // Search and Category Filter
    public string $search = '';

    public ?int $selectedCategoryId = null;

    // Cart Items: [ [ 'product_id' => 1, 'name' => '...', 'price' => 450, 'qty' => 1, 'cost' => 240, 'notes' => '' ], ... ]
    public array $cart = [];

    // Order Details
    public ?int $currentOrderId = null;

    public string $orderNumber = '';

    public ?int $customerId = null;

    public string $customerName = '';

    public string $customerPhone = '';

    public string $customerAddress = '';

    public string $orderNotes = '';

    // Direct Product Code / Barcode Input
    public string $productCodeInput = '';

    // Dine-In Specific
    public ?int $selectedTableId = null;

    public string $selectedTableName = '';

    // Delivery Specific
    public ?int $deliveryAreaId = null;

    public $deliveryCharge = 0.00;

    public ?int $deliveryRiderId = null;

    public ?float $riderStartingKm = null;

    // Financials
    public string $discountType = 'fixed'; // fixed, percent

    public $discountRate = 0.00;

    public $taxRate = 0.00; // configurable

    // Checkout Modal State
    public bool $showCheckoutModal = false;

    public $tenderedAmount = 0.00;

    public string $paymentMethod = 'cash'; // cash, card, bank, credit, split

    public string $paymentReference = '';

    // Split Payment Rows: [ ['method' => 'cash', 'amount' => 1000, 'reference' => ''], ... ]
    public array $splitPayments = [];

    // Receipt Modal State
    public bool $showReceiptModal = false;

    public ?Order $completedOrder = null;

    // Hold / Open Orders Modal
    public bool $showOpenOrdersModal = false;

    // Table Transfer Modal
    public bool $showTableTransferModal = false;

    // Delivery Setup Modal
    public bool $showDeliveryModal = false;

    public ?int $transferToTableId = null;

    // Open Shift Modal State (Enforcing shift before punching orders)
    public bool $showOpenShiftModal = false;

    public $posOpeningCash = 5000.00;

    public string $posShiftNotes = '';

    // Item Note Modal
    public bool $showNoteModal = false;

    public ?int $editingItemIndex = null;

    public string $editingItemNote = '';

    // Size / Variation Modal State
    public bool $showVariantModal = false;

    public ?int $selectedParentProductId = null;

    public ?Product $selectedParentProduct = null;

    // UI Message
    public string $notificationMessage = '';

    public string $notificationType = 'success';

    public function getActiveShift(): ?CashShift
    {
        return app(CashShiftService::class)->getActiveShift();
    }

    public function ensureShiftIsOpen(): bool
    {
        if (! $this->getActiveShift()) {
            $this->showOpenShiftModal = true;
            $this->notify('No active cash shift! Please open a shift before punching orders.', 'warning');

            return false;
        }

        return true;
    }

    public function openShiftFromPos()
    {
        try {
            $shiftService = app(CashShiftService::class);
            $shift = $shiftService->openShift(
                max(0, (float) $this->posOpeningCash),
                $this->posShiftNotes ?: 'Opened via Live POS terminal'
            );

            $this->showOpenShiftModal = false;
            $this->notify("Shift #{$shift->id} opened with Rs. ".number_format($shift->opening_cash).' starting float! Ready for punching.', 'success');
        } catch (Exception $e) {
            $this->notify('Error opening shift: '.$e->getMessage(), 'danger');
        }
    }

    public function mount(?int $orderId = null)
    {
        if ($orderId) {
            $this->loadExistingOrder($orderId);
        } else {
            $this->resetNewOrder('TAKEAWAY');
        }
    }

    public function render()
    {
        $categories = Category::where('is_active', true)->get();

        $productsQuery = Product::where('is_active', true)
            ->where('type', '!=', 'raw_material')
            ->whereNull('parent_id')
            ->with(['category', 'unit', 'variants' => function ($q) {
                $q->where('is_active', true)->orderBy('sale_price', 'asc');
            }]);

        if ($this->selectedCategoryId) {
            $productsQuery->where('category_id', $this->selectedCategoryId);
        }

        if (! empty($this->search)) {
            $term = '%'.$this->search.'%';
            $productsQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('barcode', 'like', $term)
                    ->orWhere('sku', 'like', $term);
            });
        }

        $products = $productsQuery->take(30)->get();
        $sections = TableSection::with(['tables'])->where('is_active', true)->get();
        $deliveryAreas = DeliveryArea::where('is_active', true)->get();
        $riders = DeliveryRider::where('is_active', true)->get();
        $customers = Customer::where('is_active', true)->take(20)->get();

        // Get Cash Shift Status
        $cashShiftService = app(CashShiftService::class);
        $activeShift = $cashShiftService->getActiveShift();

        return view('livewire.pos.pos-screen', [
            'categories' => $categories,
            'products' => $products,
            'sections' => $sections,
            'deliveryAreas' => $deliveryAreas,
            'riders' => $riders,
            'customers' => $customers,
            'activeShift' => $activeShift,
            'subtotal' => $this->calculateSubtotal(),
            'discountAmount' => $this->calculateDiscountAmount(),
            'taxAmount' => $this->calculateTaxAmount(),
            'grandTotal' => $this->calculateGrandTotal(),
        ])->layout('layouts.pos', ['title' => 'Point of Sale - Food Point']);
    }

    public function setOrderType(string $type)
    {
        $this->orderType = strtoupper($type);
        if ($this->orderType === 'TAKEAWAY') {
            $this->selectedTableId = null;
            $this->selectedTableName = '';
            $this->deliveryAreaId = null;
            $this->deliveryCharge = 0.00;
            $this->deliveryRiderId = null;
        } elseif ($this->orderType === 'DINE_IN') {
            $this->deliveryAreaId = null;
            $this->deliveryCharge = 0.00;
            $this->deliveryRiderId = null;
        } elseif ($this->orderType === 'DELIVERY') {
            $this->selectedTableId = null;
            $this->selectedTableName = '';
        }

        // When starting or modifying an un-saved order, regenerate prefix matching the new type (TAK, DIN, DEL)
        if (! $this->currentOrderId) {
            $this->orderNumber = app(OrderService::class)->generateOrderNumber($this->orderType);
        }
    }

    public function selectCategory(?int $catId = null)
    {
        $this->selectedCategoryId = $catId;
    }

    public function addToCart(int $productId)
    {
        if (! $this->ensureShiftIsOpen()) {
            return;
        }

        $product = Product::with(['variants' => function ($q) {
            $q->where('is_active', true)->orderBy('sale_price', 'asc');
        }])->find($productId);

        if (! $product || $product->isRawMaterial()) {
            return;
        }

        // If product has size variants, open Size Selector Modal
        if ($product->has_variants && $product->variants->isNotEmpty()) {
            $this->selectedParentProductId = $product->id;
            $this->selectedParentProduct = $product;
            $this->showVariantModal = true;

            return;
        }

        $this->addProductToCartWithQty($product, 1.0);
    }

    public function selectVariantAndAddToCart(int $variantId)
    {
        if (! $this->ensureShiftIsOpen()) {
            return;
        }

        $variant = Product::find($variantId);
        if (! $variant || $variant->isRawMaterial()) {
            $this->notify('Selected size is not available.', 'danger');
            $this->closeVariantModal();

            return;
        }

        $this->addProductToCartWithQty($variant, 1.0);
        $this->closeVariantModal();
    }

    public function closeVariantModal()
    {
        $this->showVariantModal = false;
        $this->selectedParentProductId = null;
        $this->selectedParentProduct = null;
        $this->dispatch('refocus-pos-inputs');
    }

    public function addProductToCartWithQty(Product $product, float $qty = 1.0)
    {
        if (! $this->ensureShiftIsOpen()) {
            return;
        }

        foreach ($this->cart as $index => $item) {
            if ($item['product_id'] === $product->id) {
                $this->cart[$index]['qty'] += $qty;
                $this->notify("Added {$qty}x {$product->name}");

                return;
            }
        }

        $this->cart[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'name_ur' => $product->name_ur,
            'sku' => $product->sku,
            'price' => (float) $product->sale_price,
            'cost' => (float) $product->cost_price,
            'qty' => $qty,
            'notes' => '',
        ];

        $this->notify("Added {$qty}x {$product->name} to order");
    }

    public function handleProductCodeEnter()
    {
        $input = trim($this->productCodeInput);

        // If code is empty and cart has items, transition focus to the Save button!
        if (empty($input)) {
            if (! empty($this->cart)) {
                $this->dispatch('focus-save-button');
            } else {
                $this->notify('Cart is empty. Please enter an item code or choose from menu.', 'warning');
                $this->dispatch('refocus-product-code');
            }

            return;
        }

        if (! $this->ensureShiftIsOpen()) {
            return;
        }

        // Support multiplier format like "2*BUR-01" or "BUR-01*2"
        $qty = 1.0;
        $code = $input;
        if (str_contains($input, '*')) {
            $parts = explode('*', $input);
            if (is_numeric(trim($parts[0]))) {
                $qty = max(1.0, (float) trim($parts[0]));
                $code = trim($parts[1]);
            } elseif (is_numeric(trim($parts[1]))) {
                $qty = max(1.0, (float) trim($parts[1]));
                $code = trim($parts[0]);
            }
        }

        $code = trim($code);
        $lowerCode = strtolower($code);

        // Search product by code, sku, barcode, or id
        $product = Product::where('is_active', true)
            ->where(function ($q) use ($code, $lowerCode) {
                $q->whereRaw('LOWER(code) = ?', [$lowerCode])
                    ->orWhereRaw('LOWER(sku) = ?', [$lowerCode])
                    ->orWhere('barcode', $code);
                if (is_numeric($code)) {
                    $q->orWhere('id', (int) $code);
                }
            })
            ->first();

        // If not found in products, check Deals by code or id
        if (! $product) {
            $deal = Deal::where('is_active', true)
                ->where(function ($q) use ($code, $lowerCode) {
                    $q->whereRaw('LOWER(code) = ?', [$lowerCode]);
                    if (is_numeric($code)) {
                        $q->orWhere('id', (int) $code);
                    }
                })
                ->first();

            if ($deal && $deal->product_id) {
                $product = Product::find($deal->product_id);
            }
        }

        if ($product) {
            if ($product->isRawMaterial()) {
                $this->notify("Item '{$product->name}' is a Raw Material / Ingredient and cannot be sold on POS.", 'danger');
                $this->productCodeInput = '';
                $this->dispatch('refocus-product-code');

                return;
            }

            if ($product->has_variants) {
                $product->load(['variants' => function ($q) {
                    $q->where('is_active', true)->orderBy('sale_price', 'asc');
                }]);
                if ($product->variants->isNotEmpty()) {
                    $this->selectedParentProductId = $product->id;
                    $this->selectedParentProduct = $product;
                    $this->showVariantModal = true;
                    $this->productCodeInput = '';

                    return;
                }
            }

            $this->addProductToCartWithQty($product, $qty);
            $this->productCodeInput = '';
            $this->dispatch('refocus-product-code');
        } else {
            $this->notify("No product found matching code: '{$code}'", 'danger');
            $this->dispatch('select-product-code');
        }
    }

    public function updateQty(int $index, float $delta)
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $newQty = $this->cart[$index]['qty'] + $delta;
        if ($newQty <= 0) {
            $this->removeFromCart($index);
        } else {
            $this->cart[$index]['qty'] = $newQty;
        }
    }

    public function setQty(int $index, $qty)
    {
        if (! isset($this->cart[$index])) {
            return;
        }
        $num = (float) $qty;
        if ($num <= 0) {
            $this->removeFromCart($index);
        } else {
            $this->cart[$index]['qty'] = $num;
        }
    }

    public function removeFromCart(int $index)
    {
        if (isset($this->cart[$index])) {
            $name = $this->cart[$index]['name'];
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart);
            $this->notify("Removed {$name} from order", 'warning');
        }
    }

    public function clearCart()
    {
        $this->clearOrder();
    }

    public function openItemNote(int $index)
    {
        $this->editingItemIndex = $index;
        $this->editingItemNote = $this->cart[$index]['notes'] ?? '';
        $this->showNoteModal = true;
    }

    public function saveItemNote()
    {
        if ($this->editingItemIndex !== null && isset($this->cart[$this->editingItemIndex])) {
            $this->cart[$this->editingItemIndex]['notes'] = trim($this->editingItemNote);
        }
        $this->showNoteModal = false;
        $this->editingItemIndex = null;
    }

    // Table Selection for Dine-In
    public function selectTable(int $tableId)
    {
        $table = RestaurantTable::find($tableId);
        if (! $table) {
            return;
        }

        if ($table->isOccupied() && $table->active_order_id && $table->active_order_id !== $this->currentOrderId) {
            // Load existing table order
            $this->loadExistingOrder($table->active_order_id);
            $this->notify("Loaded existing Order #{$this->orderNumber} for {$table->name}");

            return;
        }

        $this->selectedTableId = $table->id;
        $this->selectedTableName = $table->name;
        $this->notify("Assigned to {$table->name}");
    }

    // Delivery Area Selection
    public function selectDeliveryArea(int $areaId)
    {
        $area = DeliveryArea::find($areaId);
        if ($area) {
            $this->deliveryAreaId = $area->id;
            $this->deliveryCharge = (float) $area->delivery_charge;
            $this->notify("Delivery to {$area->name} (Charge: Rs. {$this->deliveryCharge})");
        }
    }

    public function updatedDeliveryAreaId($value)
    {
        if ($value) {
            $area = DeliveryArea::find($value);
            if ($area) {
                $this->deliveryCharge = (float) $area->delivery_charge;
                $this->notify("Delivery fee: Rs. {$this->deliveryCharge} for {$area->name}");
            }
        } else {
            $this->deliveryCharge = 0.00;
        }
    }

    public function updatedCustomerPhone($value)
    {
        // Enforce numeric only and 11-digit masking (e.g. 03006677991)
        $clean = preg_replace('/[^0-9]/', '', (string) $value);
        if (str_starts_with($clean, '92')) {
            $clean = preg_replace('/^920?/', '0', $clean);
        } elseif (strlen($clean) === 10 && str_starts_with($clean, '3')) {
            $clean = '0'.$clean;
        }
        $clean = substr($clean, 0, 11);
        $this->customerPhone = $clean;

        if (strlen($clean) >= 4) {
            $customer = Customer::where('mobile', $clean)->first();
            if ($customer) {
                $this->customerId = $customer->id;
                $this->customerName = $customer->name;
                $this->customerAddress = $customer->address ?? '';
                $this->notify("Loaded customer: {$customer->name}");
            }
        }
    }

    // Customer Selection
    public function selectCustomer(int $custId)
    {
        $customer = Customer::find($custId);
        if ($customer) {
            $this->customerId = $customer->id;
            $this->customerName = $customer->name;
            $this->customerPhone = $customer->mobile === 'N/A' ? '' : $customer->mobile;
            $this->customerAddress = $customer->address ?? '';
            $this->notify("Customer: {$customer->name}");
        }
    }

    /**
     * Validate that table selection is mandatory for Dine-In orders
     */
    public function validateDineInTable(): bool
    {
        if ($this->orderType === 'DINE_IN' && empty($this->selectedTableId)) {
            $this->notify('Table selection is mandatory for Dine-In orders. Please select a table.', 'danger');

            return false;
        }

        return true;
    }

    /**
     * Validate that customer name and phone number are provided before saving/punching
     */
    public function validateCustomerDetails(): bool
    {
        $name = trim($this->customerName);
        $phone = trim($this->customerPhone);

        if (empty($name) || strtolower($name) === 'walk-in customer' || strtolower($name) === 'walk-in') {
            $this->notify('Customer Name is mandatory before saving or punching an order.', 'danger');
            $this->dispatch('focus-customer-name');

            return false;
        }

        if (empty($phone) || strlen($phone) < 10) {
            $this->notify('Customer Phone Number (11 digits e.g. 03001234567) is mandatory before saving or punching an order.', 'danger');
            $this->dispatch('focus-customer-phone');

            return false;
        }

        return true;
    }

    /**
     * Resolve existing or create new customer profile in database
     */
    public function resolveCustomer(): ?Customer
    {
        $phone = trim($this->customerPhone);
        $name = trim($this->customerName);
        $address = trim($this->customerAddress);

        if ($this->customerId) {
            $customer = Customer::find($this->customerId);
            if ($customer) {
                if (! empty($name) && ! in_array(strtolower($name), ['walk-in customer', 'walk-in']) && $customer->name !== $name) {
                    $customer->name = $name;
                }
                if (! empty($phone) && $customer->mobile !== $phone) {
                    $customer->mobile = $phone;
                }
                if (! empty($address) && $customer->address !== $address) {
                    $customer->address = $address;
                }
                $customer->save();

                return $customer;
            }
        }

        if (! empty($phone)) {
            $customer = Customer::where('mobile', $phone)->first();
            if ($customer) {
                if (! empty($name) && ! in_array(strtolower($name), ['walk-in customer', 'walk-in'])) {
                    $customer->name = $name;
                }
                if (! empty($address)) {
                    $customer->address = $address;
                }
                $customer->save();
            } else {
                $customer = Customer::create([
                    'name' => (! empty($name) && ! in_array(strtolower($name), ['walk-in customer', 'walk-in'])) ? $name : 'Customer ('.$phone.')',
                    'mobile' => $phone,
                    'address' => $address ?: null,
                    'is_active' => true,
                ]);
            }
            $this->customerId = $customer->id;
            $this->customerName = $customer->name;

            return $customer;
        }

        if (! empty($name) && ! in_array(strtolower($name), ['walk-in customer', 'walk-in'])) {
            $customer = Customer::firstOrCreate(
                ['name' => $name],
                [
                    'mobile' => 'N/A',
                    'address' => $address ?: null,
                    'is_active' => true,
                ]
            );
            $this->customerId = $customer->id;

            return $customer;
        }

        return null;
    }

    // Calculation Helpers
    public function calculateSubtotal(): float
    {
        $sum = 0.00;
        foreach ($this->cart as $item) {
            $sum += ((float) $item['price'] * (float) $item['qty']);
        }

        return $sum;
    }

    public function calculateDiscountAmount(): float
    {
        $subtotal = $this->calculateSubtotal();
        $rate = is_numeric($this->discountRate) ? (float) $this->discountRate : 0.00;
        $rate = max(0.00, $rate);

        if ($this->discountType === 'percent') {
            return (float) (($subtotal * $rate) / 100.0);
        }

        return (float) min($subtotal, $rate);
    }

    public function calculateTaxAmount(): float
    {
        $taxRate = is_numeric($this->taxRate) ? (float) $this->taxRate : 0.00;
        if ($taxRate <= 0) {
            return 0.00;
        }
        $taxable = $this->calculateSubtotal() - $this->calculateDiscountAmount();

        return (float) (($taxable * $taxRate) / 100.0);
    }

    public function calculateGrandTotal(): float
    {
        $delivery = is_numeric($this->deliveryCharge) ? (float) $this->deliveryCharge : 0.00;
        $total = $this->calculateSubtotal() - $this->calculateDiscountAmount() + $this->calculateTaxAmount() + $delivery;

        return (float) max(0.00, $total);
    }

    public function updatedDiscountRate($value): void
    {
        if ($value === '' || $value === null || ! is_numeric($value)) {
            $this->discountRate = 0;
        } else {
            $this->discountRate = max(0, (float) $value);
        }
    }

    // Hold / Save Running Order (Crucial for Dine-In, Takeaway, and Open Deliveries)
    public function saveOpenOrder()
    {
        if (! $this->ensureShiftIsOpen()) {
            return;
        }

        if (! $this->validateDineInTable()) {
            return;
        }

        if (! $this->validateCustomerDetails()) {
            return;
        }

        if (empty($this->cart)) {
            $this->notify('Please add products before saving order.', 'danger');

            return;
        }

        try {
            // Determine previously kotted items if this is an existing order
            $previouslyKotted = [];
            if ($this->currentOrderId) {
                $previouslyKotted = KotItem::whereHas('kot', function ($q) {
                    $q->where('order_id', $this->currentOrderId);
                })
                    ->selectRaw('product_id, SUM(quantity) as total_qty')
                    ->groupBy('product_id')
                    ->pluck('total_qty', 'product_id')
                    ->all();
            }

            // Calculate delta items to send in the new KOT
            $deltaItems = [];
            foreach ($this->cart as $item) {
                $prodId = $item['product_id'];
                $currentQty = (float) $item['qty'];
                $prevQty = (float) ($previouslyKotted[$prodId] ?? 0.0);

                if ($currentQty > $prevQty) {
                    $diffQty = $currentQty - $prevQty;
                    $deltaItems[] = [
                        'product_id' => $prodId,
                        'name' => $item['name'],
                        'name_ur' => $item['name_ur'] ?? null,
                        'qty' => $diffQty,
                        'notes' => $item['notes'] ?? '',
                    ];
                }
            }

            $order = $this->persistOrderToDatabase('draft');
            $savedId = $order->id;
            $savedNumber = $order->order_number;

            // If there are new or increased items, generate a new sequential KOT ticket!
            if (! empty($deltaItems)) {
                $nextKotNumber = ($order->kots()->max('kot_number') ?? 0) + 1;
                $kot = Kot::create([
                    'order_id' => $order->id,
                    'kot_number' => $nextKotNumber,
                    'status' => 'prep',
                    'user_id' => auth()->id(),
                    'notes' => $this->orderNotes,
                ]);

                foreach ($deltaItems as $dItem) {
                    $kot->items()->create([
                        'product_id' => $dItem['product_id'],
                        'product_name' => $dItem['name'],
                        'product_name_ur' => $dItem['name_ur'],
                        'quantity' => $dItem['qty'],
                        'notes' => $dItem['notes'] ?: null,
                        'status' => 'prep',
                    ]);
                }

                $this->notify("Order #{$savedNumber} saved! Kitchen KOT #{$kot->kot_number} printing...", 'success');
                $this->dispatch('print-kot', url: route('restaurant.kot.print', ['id' => $savedId, 'kot_id' => $kot->id]));
            } else {
                $this->notify("Order #{$savedNumber} saved! No new items to send to kitchen.", 'info');
            }

            // Refresh Live POS terminal for the next order
            $this->resetNewOrder($this->orderType);
        } catch (Exception $e) {
            $this->notify('Error saving order: '.$e->getMessage(), 'danger');
        }
    }

    public function updateOrderKotStatus(int $orderId, string $status)
    {
        $order = Order::find($orderId);
        if ($order) {
            $order->kot_status = $status;
            $order->save();
            $this->notify("Order #{$order->order_number} KOT stage updated to ".ucfirst($status));
        }
    }

    // Save and Print Unpaid Bill (For Customer / Rider, then refresh for next order)
    public function saveAndPrintUnpaidBill()
    {
        if (! $this->ensureShiftIsOpen()) {
            return;
        }

        if (! $this->validateDineInTable()) {
            return;
        }

        if (! $this->validateCustomerDetails()) {
            return;
        }

        if (empty($this->cart)) {
            $this->notify('Please add products to cart before printing bill.', 'danger');

            return;
        }

        try {
            $order = $this->persistOrderToDatabase('draft');
            $savedId = $order->id;
            $savedNumber = $order->order_number;

            $this->notify("Order #{$savedNumber} saved! Printing unpaid bill...", 'success');

            // Dispatch event to browser to print thermal bill
            $this->dispatch('print-bill', url: route('orders.thermal', $savedId));

            // Refresh Live POS terminal for the next order
            $this->resetNewOrder($this->orderType);
        } catch (Exception $e) {
            $this->notify('Error printing bill: '.$e->getMessage(), 'danger');
        }
    }

    // Open Checkout Modal
    public function openCheckout()
    {
        if (! $this->ensureShiftIsOpen()) {
            return;
        }

        if (! $this->validateDineInTable()) {
            return;
        }

        if (! $this->validateCustomerDetails()) {
            return;
        }

        if (empty($this->cart)) {
            $this->notify('Please add products to cart before checkout.', 'danger');

            return;
        }

        $grandTotal = $this->calculateGrandTotal();
        $this->tenderedAmount = $grandTotal;
        $this->paymentMethod = 'cash';
        $this->paymentReference = '';
        $this->splitPayments = [
            ['method' => 'cash', 'amount' => $grandTotal, 'reference' => ''],
        ];
        $this->showCheckoutModal = true;
    }

    public function closeCheckout()
    {
        $this->showCheckoutModal = false;
    }

    public function setQuickTender(float $amount)
    {
        $this->tenderedAmount = $amount;
    }

    public function addSplitPaymentRow()
    {
        $this->splitPayments[] = ['method' => 'card', 'amount' => 0.00, 'reference' => ''];
    }

    public function removeSplitPaymentRow(int $index)
    {
        unset($this->splitPayments[$index]);
        $this->splitPayments = array_values($this->splitPayments);
    }

    // Complete Checkout / Cash Out
    public function processCheckout()
    {
        if (! $this->validateDineInTable()) {
            return;
        }

        if (! $this->validateCustomerDetails()) {
            return;
        }

        try {
            $orderService = app(OrderService::class);
            $paymentService = app(PaymentService::class);

            // 1. Persist or update order record
            $order = $this->persistOrderToDatabase('draft');

            $grandTotal = (float) $order->grand_total;

            // 2. Process payments based on method
            if ($this->paymentMethod === 'split') {
                $paymentService->processSplitPayments($order, $this->splitPayments);
            } else {
                $paidAmt = (float) $this->tenderedAmount;
                // If paid more than total in cash, paid is capped to total, change is given
                $actualRecordedPaid = min($grandTotal, $paidAmt);
                $paymentService->recordPayment(
                    $order,
                    $this->paymentMethod,
                    $actualRecordedPaid,
                    $this->paymentReference
                );
            }

            // 3. Finalize Order (Posts Accounting, Deducts Stock, Releases Table)
            $orderService->finalizeOrder($order);

            // 4. Set completed order for receipt modal
            $this->completedOrder = $order->fresh(['items', 'payments', 'customer', 'table', 'deliveryArea', 'rider', 'cashier']);
            $this->showCheckoutModal = false;
            $this->showReceiptModal = true;

            $this->notify("Order #{$order->order_number} successfully completed and printed!");

            // Reset for next order
            $this->resetNewOrder($this->orderType);
        } catch (Exception $e) {
            $this->notify('Checkout failed: '.$e->getMessage(), 'danger');
        }
    }

    // Persist current state to Order model
    protected function persistOrderToDatabase(string $status = 'draft'): Order
    {
        if ($this->orderType === 'DINE_IN' && empty($this->selectedTableId)) {
            throw new Exception('Table selection is mandatory for Dine-In orders. Please select a table before saving.');
        }

        $orderService = app(OrderService::class);

        // Auto-resolve or create customer profile
        $resolvedCustomer = $this->resolveCustomer();
        if ($resolvedCustomer) {
            $this->customerId = $resolvedCustomer->id;
        }

        if ($this->currentOrderId) {
            $order = Order::findOrFail($this->currentOrderId);
            $order->order_type = $this->orderType;
            $order->customer_id = $this->customerId;
            $order->customer_name = $this->customerName;
            $order->customer_phone = $this->customerPhone;
            $order->customer_address = $this->customerAddress;
            $order->table_id = $this->selectedTableId;
            $order->table_name = $this->selectedTableName;
            $order->delivery_area_id = $this->deliveryAreaId;
            $order->delivery_charge = $this->deliveryCharge;
            if ($this->deliveryAreaId && (! $order->delivery_distance_km || $order->delivery_distance_km == 0)) {
                $area = DeliveryArea::find($this->deliveryAreaId);
                if ($area) {
                    $order->delivery_distance_km = (float) $area->estimated_distance_km;
                }
            }
            $order->delivery_rider_id = $this->deliveryRiderId;
            $order->discount_type = $this->discountType;
            $order->discount_rate = $this->discountRate;
            $order->notes = $this->orderNotes;
            if (! $order->cash_shift_id && $this->getActiveShift()) {
                $order->cash_shift_id = $this->getActiveShift()->id;
            }
            $order->save();

            // Sync items
            $order->items()->delete();
            foreach ($this->cart as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'product_name_ur' => $item['name_ur'] ?? null,
                    'product_sku' => $item['sku'] ?? null,
                    'quantity' => $item['qty'],
                    'unit_cost' => $item['cost'],
                    'unit_price' => $item['price'],
                    'discount_amount' => 0.00,
                    'tax_amount' => 0.00,
                    'subtotal' => $item['qty'] * $item['price'],
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }

            $orderService->recalculateOrder($order);

            return $order;
        }

        // Create new order
        $area = $this->deliveryAreaId ? DeliveryArea::find($this->deliveryAreaId) : null;
        $order = $orderService->createOrder([
            'order_type' => $this->orderType,
            'customer_id' => $this->customerId,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'customer_address' => $this->customerAddress,
            'table_id' => $this->selectedTableId,
            'table_name' => $this->selectedTableName,
            'delivery_area_id' => $this->deliveryAreaId,
            'delivery_charge' => $this->deliveryCharge,
            'delivery_distance_km' => $area ? (float) $area->estimated_distance_km : 0.00,
            'delivery_rider_id' => $this->deliveryRiderId,
            'discount_type' => $this->discountType,
            'discount_rate' => $this->discountRate,
            'notes' => $this->orderNotes,
            'cash_shift_id' => $this->getActiveShift()?->id,
        ]);

        foreach ($this->cart as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'product_name_ur' => $item['name_ur'] ?? null,
                'product_sku' => $item['sku'] ?? null,
                'quantity' => $item['qty'],
                'unit_cost' => $item['cost'],
                'unit_price' => $item['price'],
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'subtotal' => $item['qty'] * $item['price'],
                'notes' => $item['notes'] ?? null,
                'status' => 'pending',
            ]);
        }

        $orderService->recalculateOrder($order);

        return $order;
    }

    public function loadExistingOrder(int $orderId)
    {
        $order = Order::with(['items.product', 'table', 'customer', 'deliveryArea', 'kots'])->findOrFail($orderId);
        $this->currentOrderId = $order->id;
        $this->orderNumber = $order->order_number;
        $this->orderType = $order->order_type;
        $this->customerId = $order->customer_id;
        $this->customerName = $order->customer_name ?? '';
        $this->customerPhone = $order->customer_phone ?? '';
        $this->customerAddress = $order->customer_address ?? '';
        $this->selectedTableId = $order->table_id;
        $this->selectedTableName = $order->table_name ?? '';
        $this->deliveryAreaId = $order->delivery_area_id;
        $this->deliveryCharge = (float) $order->delivery_charge;
        $this->deliveryRiderId = $order->delivery_rider_id;
        $this->discountType = $order->discount_type;
        $this->discountRate = (float) $order->discount_rate;
        $this->orderNotes = $order->notes ?? '';

        $this->cart = [];
        foreach ($order->items as $item) {
            $this->cart[] = [
                'product_id' => $item->product_id,
                'name' => $item->product_name,
                'name_ur' => $item->product_name_ur ?: $item->product?->name_ur,
                'sku' => $item->product_sku,
                'price' => (float) $item->unit_price,
                'cost' => (float) $item->unit_cost,
                'qty' => (float) $item->quantity,
                'notes' => $item->notes ?? '',
            ];
        }

        $this->showOpenOrdersModal = false;
    }

    public function resetNewOrder(string $type = 'TAKEAWAY')
    {
        $this->currentOrderId = null;
        $this->orderType = strtoupper($type);
        $this->orderNumber = app(OrderService::class)->generateOrderNumber($this->orderType);
        $this->cart = [];
        $this->customerId = null;
        $this->customerName = '';
        $this->customerPhone = '';
        $this->customerAddress = '';
        $this->orderNotes = '';
        $this->selectedTableId = null;
        $this->selectedTableName = '';
        $this->deliveryAreaId = null;
        $this->deliveryCharge = 0.00;
        $this->deliveryRiderId = null;
        $this->discountType = 'fixed';
        $this->discountRate = 0.00;
        $this->productCodeInput = '';
        $this->showDeliveryModal = false;
        $this->dispatch('order-saved-reset');
    }

    public function clearOrder()
    {
        $this->resetNewOrder($this->orderType);
        $this->notify('Order fields and cart have been cleared.', 'info');
    }

    public function cancelOpenOrder(int $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            if ($order->isFinalized()) {
                $this->notify('Finalized orders cannot be cancelled directly; please process a Sale Return.', 'danger');

                return;
            }

            $order->order_status = 'cancelled';
            $order->notes = trim(($order->notes ? $order->notes.' | ' : '').'Cancelled from Live POS');
            $order->save();

            if ($order->table) {
                app(TableService::class)->releaseTable($order->table);
            }

            if ($this->currentOrderId === $order->id) {
                $this->resetNewOrder($this->orderType);
            }

            $this->notify("Order #{$order->order_number} has been cancelled.", 'warning');
        } catch (Exception $e) {
            $this->notify('Failed to cancel order: '.$e->getMessage(), 'danger');
        }
    }

    // Table Transfer
    public function openTableTransfer()
    {
        if (! $this->selectedTableId || ! $this->currentOrderId) {
            $this->notify('Please save the Dine-In order to a table first.', 'warning');

            return;
        }
        $this->transferToTableId = null;
        $this->showTableTransferModal = true;
    }

    public function executeTableTransfer()
    {
        if (! $this->transferToTableId) {
            $this->notify('Please select a destination table.', 'danger');

            return;
        }

        try {
            $order = Order::findOrFail($this->currentOrderId);
            $targetTable = RestaurantTable::findOrFail($this->transferToTableId);
            app(TableService::class)->transferTable($order, $targetTable);

            $this->selectedTableId = $targetTable->id;
            $this->selectedTableName = $targetTable->name;
            $this->showTableTransferModal = false;
            $this->notify("Order successfully transferred to {$targetTable->name}!");
        } catch (Exception $e) {
            $this->notify('Transfer failed: '.$e->getMessage(), 'danger');
        }
    }

    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->completedOrder = null;
        $this->dispatch('refocus-pos-inputs');
    }

    public function closeAnyOpenModal(): bool
    {
        $closed = false;

        if ($this->showReceiptModal) {
            $this->closeReceiptModal();
            $closed = true;
        }

        if ($this->showCheckoutModal) {
            $this->closeCheckout();
            $closed = true;
        }

        if ($this->showDeliveryModal) {
            $this->showDeliveryModal = false;
            $closed = true;
        }

        if ($this->showNoteModal) {
            $this->showNoteModal = false;
            $this->editingItemIndex = null;
            $closed = true;
        }

        if ($this->showTableTransferModal) {
            $this->showTableTransferModal = false;
            $this->transferToTableId = null;
            $closed = true;
        }

        if ($this->showOpenOrdersModal) {
            $this->showOpenOrdersModal = false;
            $closed = true;
        }

        if ($this->showOpenShiftModal) {
            $this->showOpenShiftModal = false;
            $closed = true;
        }

        if ($this->showVariantModal) {
            $this->closeVariantModal();
            $closed = true;
        }

        if ($closed) {
            $this->dispatch('refocus-pos-inputs');
        }

        return $closed;
    }

    protected function notify(string $message, string $type = 'success')
    {
        $this->notificationMessage = $message;
        $this->notificationType = $type;
    }
}
