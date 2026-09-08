<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Category;
use App\Models\Kot;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KotIncrementalAndUrduSupportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        app(CashShiftService::class)->openShift(5000.00, 'Morning Cash Shift', $this->user->id);
    }

    public function test_customer_name_and_phone_number_are_mandatory_to_save_order(): void
    {
        $category = Category::create(['name' => 'Burgers', 'slug' => 'burgers', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Zinger Burger',
            'name_ur' => 'زنگر برگر',
            'code' => 'ZNG-01',
            'category_id' => $category->id,
            'sale_price' => 450.00,
            'cost_price' => 200.00,
            'is_active' => true,
        ]);

        // 1. Trying to save with empty customer details fails and doesn't dispatch print-kot
        Livewire::test(PosScreen::class)
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('saveOpenOrder')
            ->assertNotDispatched('print-kot')
            ->assertSet('notificationType', 'danger');

        // Order was NOT created in DB
        $this->assertEquals(0, Order::count());

        // 2. Supplying valid customer name and phone allows saving
        Livewire::test(PosScreen::class)
            ->set('customerName', 'Hassan Ali')
            ->set('customerPhone', '03001234567')
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('saveOpenOrder')
            ->assertDispatched('print-kot')
            ->assertCount('cart', 0);

        $this->assertEquals(1, Order::count());
        $order = Order::first();
        $this->assertEquals('Hassan Ali', $order->customer_name);
        $this->assertEquals('03001234567', $order->customer_phone);
    }

    public function test_incremental_kot_generates_kot_2_with_only_recalled_items_and_same_order_number(): void
    {
        $category = Category::create(['name' => 'Fast Food', 'slug' => 'fast-food', 'is_active' => true]);
        $zinger = Product::create([
            'name' => 'Zinger Burger',
            'name_ur' => 'زنگر برگر',
            'code' => 'ZNG-01',
            'category_id' => $category->id,
            'sale_price' => 450.00,
            'cost_price' => 220.00,
            'is_active' => true,
        ]);

        $fries = Product::create([
            'name' => 'French Fries',
            'name_ur' => 'فرینچ فرائز',
            'code' => 'FF-01',
            'category_id' => $category->id,
            'sale_price' => 200.00,
            'cost_price' => 80.00,
            'is_active' => true,
        ]);

        // 1. Initial Punch: Order with 1x Zinger Burger -> Generates KOT #1
        $component = Livewire::test(PosScreen::class)
            ->set('orderType', 'TAKEAWAY')
            ->set('customerName', 'Usman Qureshi')
            ->set('customerPhone', '03021122334')
            ->call('addToCart', $zinger->id)
            ->call('saveOpenOrder')
            ->assertDispatched('print-kot');

        $order = Order::first();
        $initialOrderNumber = $order->order_number;

        // Verify KOT #1 exists with only 1x Zinger Burger
        $this->assertCount(1, $order->kots);
        $kot1 = $order->kots()->first();
        $this->assertEquals(1, $kot1->kot_number);
        $this->assertCount(1, $kot1->items);
        $this->assertEquals('Zinger Burger', $kot1->items->first()->product_name);
        $this->assertEquals('زنگر برگر', $kot1->items->first()->product_name_ur);
        $this->assertEquals(1.0, (float) $kot1->items->first()->quantity);

        // 2. Recall the order in POS and add 1 more Zinger Burger and 1x French Fries
        $component->call('loadExistingOrder', $order->id)
            ->assertSet('currentOrderId', $order->id)
            ->assertSet('orderNumber', $initialOrderNumber)
            ->assertCount('cart', 1)
            // Add another Zinger Burger (now 2 total) and 1 Fries
            ->call('addToCart', $zinger->id)
            ->call('addToCart', $fries->id)
            ->assertCount('cart', 2)
            // Save order again
            ->call('saveOpenOrder')
            ->assertDispatched('print-kot');

        // Refresh order from DB
        $order->refresh();

        // The order number MUST NOT change!
        $this->assertEquals($initialOrderNumber, $order->order_number);

        // A second KOT (KOT #2) MUST be generated!
        $this->assertCount(2, $order->kots);
        $kot2 = $order->kots()->where('kot_number', 2)->first();
        $this->assertNotNull($kot2);
        $this->assertEquals(2, $kot2->kot_number);

        // KOT #2 must contain ONLY the delta: 1x additional Zinger and 1x Fries!
        $this->assertCount(2, $kot2->items);
        $kot2Zinger = $kot2->items->where('product_id', $zinger->id)->first();
        $this->assertNotNull($kot2Zinger);
        $this->assertEquals(1.0, (float) $kot2Zinger->quantity); // Delta is exactly 1x, not 2x!

        $kot2Fries = $kot2->items->where('product_id', $fries->id)->first();
        $this->assertNotNull($kot2Fries);
        $this->assertEquals(1.0, (float) $kot2Fries->quantity);
        $this->assertEquals('فرینچ فرائز', $kot2Fries->product_name_ur);
    }

    public function test_kot_status_can_be_updated_across_stages(): void
    {
        $order = Order::create([
            'order_number' => 'TAK-TEST01',
            'order_type' => 'TAKEAWAY',
            'order_status' => 'draft',
            'kot_status' => 'prep',
            'customer_name' => 'Ali',
            'customer_phone' => '03001234567',
        ]);

        $this->assertEquals('prep', $order->kot_status);

        // 1. Livewire updateOrderKotStatus
        Livewire::test(PosScreen::class)
            ->call('updateOrderKotStatus', $order->id, 'marination');

        $this->assertEquals('marination', $order->fresh()->kot_status);

        // 2. Progress through stages: baking, packing, ready
        $this->post(route('restaurant.kitchen.order.status', $order->id), ['kot_status' => 'baking'])
            ->assertRedirect();
        $this->assertEquals('baking', $order->fresh()->kot_status);

        $this->post(route('restaurant.kitchen.order.status', $order->id), ['kot_status' => 'packing'])
            ->assertRedirect();
        $this->assertEquals('packing', $order->fresh()->kot_status);

        $this->post(route('restaurant.kitchen.order.status', $order->id), ['kot_status' => 'ready'])
            ->assertRedirect();
        $this->assertEquals('ready', $order->fresh()->kot_status);
    }

    public function test_menu_catalog_supports_urdu_name_and_displays_on_kot(): void
    {
        $category = Category::create(['name' => 'Pizzas', 'slug' => 'pizzas', 'is_active' => true]);

        // 1. Store via ProductController
        $this->post(route('resources.products.store'), [
            'name' => 'Fajita Pizza',
            'name_ur' => 'فجیتا پیزا',
            'code' => 'PIZ-FAJ',
            'category_id' => $category->id,
            'sale_price' => 1200.00,
            'cost_price' => 600.00,
        ])->assertRedirect();

        $product = Product::where('code', 'PIZ-FAJ')->first();
        $this->assertNotNull($product);
        $this->assertEquals('فجیتا پیزا', $product->name_ur);

        // 2. Punch order with this product
        Livewire::test(PosScreen::class)
            ->set('customerName', 'Kamran Akmal')
            ->set('customerPhone', '03009988776')
            ->call('addToCart', $product->id)
            ->call('saveOpenOrder');

        $order = Order::latest('id')->first();
        $kot = $order->kots()->first();

        // 3. Thermal KOT Print route renders both English and Urdu names!
        $response = $this->get(route('restaurant.kot.print', ['id' => $order->id, 'kot_id' => $kot->id]));
        $response->assertOk();
        $response->assertSee('Fajita Pizza');
        $response->assertSee('فجیتا پیزا');
        $response->assertSee('KOT #1');
        $response->assertSee($order->order_number);
    }
}
