<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Deal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\TableSection;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use App\Services\Inventory\StockService;
use App\Services\Sales\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealsAndKitchenPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_save_open_order_dispatches_print_kot_event(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        app(CashShiftService::class)->openShift(5000.00, 'Test Shift', $user->id);

        $category = Category::firstOrCreate(['name' => 'Fast Food'], ['slug' => 'fast-food', 'is_active' => true]);
        $product = Product::firstOrCreate(
            ['code' => 'TEST-001'],
            [
                'name' => 'Test Burger',
                'sku' => 'TEST-001',
                'category_id' => $category->id,
                'sale_price' => 500.00,
                'cost_price' => 300.00,
                'is_active' => true,
            ]
        );

        Livewire::test(PosScreen::class)
            ->set('customerName', 'Tariq Mehmood')
            ->set('customerPhone', '03001234567')
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('saveOpenOrder')
            ->assertDispatched('print-kot')
            ->assertCount('cart', 0); // POS terminal refreshed for next order
    }

    public function test_pos_phone_lookup_auto_populates_name_and_address(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $customer = Customer::create([
            'name' => 'Muhammad Bilal',
            'mobile' => '03001234567',
            'address' => 'House 55, Street 10, Sector G-9, Islamabad',
        ]);

        Livewire::test(PosScreen::class)
            ->set('customerPhone', '03001234567')
            ->assertSet('customerName', 'Muhammad Bilal')
            ->assertSet('customerAddress', 'House 55, Street 10, Sector G-9, Islamabad');
    }

    public function test_packages_and_deals_crud_and_pos_sync(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $category = Category::firstOrCreate(['name' => 'Meals'], ['slug' => 'meals', 'is_active' => true]);

        $item1 = Product::create([
            'name' => 'Classic Chicken Burger',
            'code' => 'BUR-01',
            'category_id' => $category->id,
            'sale_price' => 450.00,
            'cost_price' => 250.00,
            'current_stock' => 50,
            'is_active' => true,
        ]);

        $item2 = Product::create([
            'name' => 'Crispy French Fries',
            'code' => 'FRY-01',
            'category_id' => $category->id,
            'sale_price' => 200.00,
            'cost_price' => 80.00,
            'current_stock' => 50,
            'is_active' => true,
        ]);

        // 1. Create Deal via Store Route
        $response = $this->post(route('resources.deals.store'), [
            'name' => 'Mega Burger Feast Deal',
            'code' => 'DEAL-MEGA-01',
            'sale_price' => 550.00, // Regular total = 450 + 200 = 650
            'description' => '1 Burger + 1 Fries Combo',
            'items' => [
                ['product_id' => $item1->id, 'quantity' => 1],
                ['product_id' => $item2->id, 'quantity' => 1],
            ],
        ]);

        $response->assertRedirect(route('resources.deals.index'));
        $this->assertDatabaseHas('deals', ['code' => 'DEAL-MEGA-01', 'sale_price' => 550.00]);

        $deal = Deal::where('code', 'DEAL-MEGA-01')->first();
        $this->assertNotNull($deal);
        $this->assertEquals(2, $deal->items()->count());
        $this->assertEquals(650.00, $deal->originalTotalValue());
        $this->assertEquals(100.00, $deal->savingsAmount());

        // Verify Deal is synced to Product catalog under "Packages & Deals"
        $this->assertNotNull($deal->product_id);
        $syncedProduct = Product::find($deal->product_id);
        $this->assertNotNull($syncedProduct);
        $this->assertEquals('Mega Burger Feast Deal', $syncedProduct->name);
        $this->assertEquals('DEAL-MEGA-01', $syncedProduct->code);
        $this->assertEquals(550.00, (float) $syncedProduct->sale_price);
        $this->assertEquals('Packages & Deals', $syncedProduct->category->name);

        // 2. Update Deal
        $updateResponse = $this->put(route('resources.deals.update', $deal->id), [
            'name' => 'Mega Burger Feast Deal (Updated)',
            'code' => 'DEAL-MEGA-01',
            'sale_price' => 580.00,
            'description' => 'Updated Combo',
            'items' => [
                ['product_id' => $item1->id, 'quantity' => 2],
            ],
        ]);
        $updateResponse->assertRedirect();
        $this->assertEquals('Mega Burger Feast Deal (Updated)', $deal->fresh()->name);
        $this->assertEquals(580.00, (float) $deal->fresh()->sale_price);

        // 3. Toggle Status
        $this->post(route('resources.deals.toggle-status', $deal->id));
        $this->assertFalse((bool) $deal->fresh()->is_active);

        // 4. Delete Deal
        $this->delete(route('resources.deals.destroy', $deal->id));
        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
        $this->assertDatabaseMissing('products', ['id' => $syncedProduct->id]);
    }

    public function test_deal_order_finalization_deducts_constituent_items_stock(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $category = Category::firstOrCreate(['name' => 'Meals'], ['slug' => 'meals', 'is_active' => true]);

        $burger = Product::create([
            'name' => 'Patty Burger',
            'code' => 'PAT-01',
            'category_id' => $category->id,
            'sale_price' => 300.00,
            'cost_price' => 180.00,
            'current_stock' => 100,
            'is_active' => true,
        ]);

        $coke = Product::create([
            'name' => 'Can Soda 250ml',
            'code' => 'SOD-01',
            'category_id' => $category->id,
            'sale_price' => 120.00,
            'cost_price' => 60.00,
            'current_stock' => 100,
            'is_active' => true,
        ]);

        $deal = Deal::create([
            'name' => 'Lunch Deal 1',
            'code' => 'LUNCH-01',
            'sale_price' => 380.00,
            'is_active' => true,
        ]);

        $deal->items()->createMany([
            ['product_id' => $burger->id, 'quantity' => 2, 'unit_price' => 300.00],
            ['product_id' => $coke->id, 'quantity' => 1, 'unit_price' => 120.00],
        ]);

        $dealProduct = $deal->syncProduct();

        // Create an order containing 2x Lunch Deal 1
        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'order_type' => 'TAKEAWAY',
            'customer_name' => 'Deal Customer',
            'user_id' => $user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $dealProduct->id,
            'product_name' => $dealProduct->name,
            'quantity' => 2,
            'unit_price' => 380.00,
            'unit_cost' => $dealProduct->cost_price,
            'subtotal' => 760.00,
            'grand_total' => 760.00,
        ]);

        $stockService = app(StockService::class);
        $stockService->deductOrderStock($order);

        // 2x Deal means:
        // Burger: 2 deals * 2 quantity = 4 deducted (100 - 4 = 96)
        // Coke: 2 deals * 1 quantity = 2 deducted (100 - 2 = 98)
        $this->assertEquals(96.00, (float) $burger->fresh()->current_stock);
        $this->assertEquals(98.00, (float) $coke->fresh()->current_stock);
    }

    public function test_kot_view_renders_delivery_address_and_deal_components(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $category = Category::firstOrCreate(['name' => 'Meals'], ['slug' => 'meals', 'is_active' => true]);
        $prod = Product::create([
            'name' => 'Roll Paratha',
            'code' => 'ROL-01',
            'category_id' => $category->id,
            'sale_price' => 250.00,
            'cost_price' => 140.00,
            'is_active' => true,
        ]);

        $deal = Deal::create([
            'name' => 'Roll Combo Deal',
            'code' => 'ROL-COMBO',
            'sale_price' => 230.00,
            'is_active' => true,
        ]);
        $deal->items()->create(['product_id' => $prod->id, 'quantity' => 2, 'unit_price' => 250.00]);
        $dealProduct = $deal->syncProduct();

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'order_type' => 'DELIVERY',
            'customer_name' => 'Tariq Mehmood',
            'customer_phone' => '03211112233',
            'customer_address' => 'Flat 4B, Silver Heights, Gulberg III',
            'user_id' => $user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $dealProduct->id,
            'product_name' => $dealProduct->name,
            'quantity' => 1,
            'unit_price' => 230.00,
            'unit_cost' => $dealProduct->cost_price,
            'subtotal' => 230.00,
            'grand_total' => 230.00,
        ]);

        $response = $this->get(route('restaurant.kot.print', $order->id));
        $response->assertOk();
        $response->assertSee('Flat 4B, Silver Heights, Gulberg III');
        $response->assertSee('03211112233');
        $response->assertSee('Roll Combo Deal');
        $response->assertSee('Roll Paratha');
    }

    public function test_thermal_bill_and_invoice_render_deal_components(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $category = Category::firstOrCreate(['name' => 'Meals'], ['slug' => 'meals', 'is_active' => true]);
        $prod = Product::create([
            'name' => 'Crispy Zinger',
            'code' => 'ZIN-01',
            'category_id' => $category->id,
            'sale_price' => 500.00,
            'cost_price' => 300.00,
            'is_active' => true,
        ]);

        $deal = Deal::create([
            'name' => 'Duo Zinger Deal',
            'code' => 'DUO-ZIN',
            'sale_price' => 850.00,
            'is_active' => true,
        ]);
        $deal->items()->create(['product_id' => $prod->id, 'quantity' => 2, 'unit_price' => 500.00]);
        $dealProduct = $deal->syncProduct();

        $section = TableSection::firstOrCreate(['name' => 'Main Hall']);
        $table = RestaurantTable::first() ?? RestaurantTable::create([
            'table_number' => 'T-01',
            'name' => 'Table 1',
            'section_id' => $section->id,
            'capacity' => 4,
            'status' => 'available',
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'order_type' => 'DINE_IN',
            'table_id' => $table->id,
            'customer_name' => 'Hamza Ali',
            'user_id' => $user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $dealProduct->id,
            'product_name' => $dealProduct->name,
            'quantity' => 2, // 2x Duo Deal = 4 Zingers
            'unit_price' => 850.00,
            'unit_cost' => $dealProduct->cost_price,
            'subtotal' => 1700.00,
            'grand_total' => 1700.00,
        ]);

        // 1. Thermal bill test
        $thermalResponse = $this->get(route('orders.thermal', $order->id));
        $thermalResponse->assertOk();
        $thermalResponse->assertSee('Duo Zinger Deal');
        $thermalResponse->assertSee('Crispy Zinger');
        $thermalResponse->assertSee('4x Crispy Zinger');

        // 2. Order show / invoice view test
        $invoiceResponse = $this->get(route('orders.show', $order->id));
        $invoiceResponse->assertOk();
        $invoiceResponse->assertSee('Duo Zinger Deal');
        $invoiceResponse->assertSee('Crispy Zinger');
        $invoiceResponse->assertSee('4x Crispy Zinger');
    }

    public function test_pos_product_code_input_adds_product_and_navigates_to_save(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        app(CashShiftService::class)->openShift(5000.00, 'Test Shift', $user->id);

        $category = Category::firstOrCreate(['name' => 'Burgers'], ['slug' => 'burgers', 'is_active' => true]);
        $prod = Product::create([
            'name' => 'Club Sandwich',
            'code' => 'SAN-01',
            'sku' => 'SAN-01',
            'barcode' => '890123456789',
            'category_id' => $category->id,
            'sale_price' => 350.00,
            'cost_price' => 180.00,
            'is_active' => true,
        ]);

        // 1. Enter product code -> adds to cart and clears input
        Livewire::test(PosScreen::class)
            ->set('productCodeInput', 'SAN-01')
            ->call('handleProductCodeEnter')
            ->assertCount('cart', 1)
            ->assertSet('productCodeInput', '')
            ->assertDispatched('refocus-product-code')
            // 2. Enter product code with multiplier "2*SAN-01" -> updates quantity
            ->set('productCodeInput', '2*SAN-01')
            ->call('handleProductCodeEnter')
            ->assertCount('cart', 1) // same item, quantity incremented
            // 3. When code input is empty and cart has items, pressing Enter dispatches focus-save-button!
            ->set('productCodeInput', '')
            ->call('handleProductCodeEnter')
            ->assertDispatched('focus-save-button')
            // 4. Calling saveOpenOrder saves order and prints KOT
            ->set('customerName', 'Imran Malik')
            ->set('customerPhone', '03009876543')
            ->call('saveOpenOrder')
            ->assertDispatched('print-kot')
            ->assertCount('cart', 0);
    }

    public function test_pos_escape_key_cancels_all_modals_and_refocuses(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        // 1. Checkout modal
        Livewire::test(PosScreen::class)
            ->set('showCheckoutModal', true)
            ->call('closeAnyOpenModal')
            ->assertSet('showCheckoutModal', false)
            ->assertDispatched('refocus-pos-inputs');

        // 2. Receipt modal
        Livewire::test(PosScreen::class)
            ->set('showReceiptModal', true)
            ->call('closeAnyOpenModal')
            ->assertSet('showReceiptModal', false)
            ->assertDispatched('refocus-pos-inputs');

        // 3. Delivery modal
        Livewire::test(PosScreen::class)
            ->set('showDeliveryModal', true)
            ->call('closeAnyOpenModal')
            ->assertSet('showDeliveryModal', false)
            ->assertDispatched('refocus-pos-inputs');

        // 4. Note modal
        Livewire::test(PosScreen::class)
            ->set('showNoteModal', true)
            ->call('closeAnyOpenModal')
            ->assertSet('showNoteModal', false)
            ->assertDispatched('refocus-pos-inputs');

        // 5. Table Transfer modal
        Livewire::test(PosScreen::class)
            ->set('showTableTransferModal', true)
            ->call('closeAnyOpenModal')
            ->assertSet('showTableTransferModal', false)
            ->assertDispatched('refocus-pos-inputs');

        // 6. Open Orders modal
        Livewire::test(PosScreen::class)
            ->set('showOpenOrdersModal', true)
            ->call('closeAnyOpenModal')
            ->assertSet('showOpenOrdersModal', false)
            ->assertDispatched('refocus-pos-inputs');

        // 7. Open Shift modal
        Livewire::test(PosScreen::class)
            ->set('showOpenShiftModal', true)
            ->call('closeAnyOpenModal')
            ->assertSet('showOpenShiftModal', false)
            ->assertDispatched('refocus-pos-inputs');
    }
}
