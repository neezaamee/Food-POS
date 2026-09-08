<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Kot;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use Database\Seeders\FoodPointRestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FoodPointRestaurantSeeder::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_pos_catalog_api_returns_products_with_urdu_names_tables_and_active_shift(): void
    {
        $shift = app(CashShiftService::class)->openShift(4000.00, 'Morning Shift', $this->user->id);

        $category = Category::create(['name' => 'Burgers', 'slug' => 'burgers', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Zinger Burger',
            'name_ur' => 'زنگر برگر',
            'code' => 'ZNG-01',
            'category_id' => $category->id,
            'sale_price' => 450.00,
            'cost_price' => 220.00,
            'is_active' => true,
        ]);

        $table = RestaurantTable::first();

        $response = $this->getJson(route('pos.api.catalog'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment([
                'id' => $product->id,
                'name' => 'Zinger Burger',
                'name_ur' => 'زنگر برگر',
                'code' => 'ZNG-01',
            ])
            ->assertJsonFragment([
                'id' => $shift->id,
                'opening_cash' => 4000.00,
            ]);
    }

    public function test_offline_takeaway_order_sync_creates_order_and_kot_ticket(): void
    {
        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Chicken Tikka Pizza',
            'name_ur' => 'چکن تکہ پیزا',
            'code' => 'PIZ-01',
            'category_id' => $category->id,
            'sale_price' => 950.00,
            'cost_price' => 400.00,
            'is_active' => true,
        ]);

        $uuid = (string) Str::uuid();
        $payload = [
            'orders' => [
                [
                    'client_uuid' => $uuid,
                    'offline_order_number' => 'OFF-TAK-260908-0001',
                    'order_type' => 'TAKEAWAY',
                    'customer_name' => 'Muhammad Bilal',
                    'customer_phone' => '03009876543',
                    'subtotal' => 1900.00,
                    'grand_total' => 1900.00,
                    'is_finalized' => true,
                    'payment_method' => 'cash',
                    'paid_amount' => 1900.00,
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'name_ur' => $product->name_ur,
                            'price' => 950.00,
                            'qty' => 2,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('pos.api.sync'), $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'synced_count' => 1,
            ]);

        // Verify order saved in database
        $order = Order::where('client_uuid', $uuid)->first();
        $this->assertNotNull($order);
        $this->assertEquals('OFF-TAK-260908-0001', $order->offline_order_number);
        $this->assertTrue($order->is_offline);
        $this->assertEquals('Muhammad Bilal', $order->customer_name);
        $this->assertEquals('03009876543', $order->customer_phone);
        $this->assertEquals(1900.00, (float) $order->grand_total);
        $this->assertEquals('paid', $order->payment_status);

        // Verify KOT ticket created with Urdu support
        $kot = Kot::where('order_id', $order->id)->first();
        $this->assertNotNull($kot);
        $this->assertEquals(1, $kot->kot_number);
        $kotItem = $kot->items()->first();
        $this->assertNotNull($kotItem);
        $this->assertEquals('Chicken Tikka Pizza', $kotItem->product_name);
        $this->assertEquals('چکن تکہ پیزا', $kotItem->product_name_ur);
        $this->assertEquals(2, $kotItem->quantity);

        // Verify Customer record was created/resolved
        $customer = Customer::where('mobile', '03009876543')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('Muhammad Bilal', $customer->name);
    }

    public function test_offline_sync_idempotency_prevents_duplicate_orders(): void
    {
        $category = Category::create(['name' => 'Drinks', 'slug' => 'drinks', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Soft Drink Can',
            'code' => 'DRK-01',
            'category_id' => $category->id,
            'sale_price' => 120.00,
            'cost_price' => 70.00,
            'is_active' => true,
        ]);

        $uuid = (string) Str::uuid();
        $payload = [
            'orders' => [
                [
                    'client_uuid' => $uuid,
                    'offline_order_number' => 'OFF-TAK-260908-0002',
                    'order_type' => 'TAKEAWAY',
                    'customer_name' => 'Ali Raza',
                    'customer_phone' => '03123456789',
                    'subtotal' => 120.00,
                    'grand_total' => 120.00,
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'price' => 120.00,
                            'qty' => 1,
                        ],
                    ],
                ],
            ],
        ];

        // First sync creates order
        $firstResponse = $this->postJson(route('pos.api.sync'), $payload);
        $firstResponse->assertOk()->assertJson(['synced_count' => 1]);
        $this->assertEquals(1, Order::where('client_uuid', $uuid)->count());

        // Second sync with identical client_uuid returns already_synced without duplication
        $secondResponse = $this->postJson(route('pos.api.sync'), $payload);
        $secondResponse->assertOk()
            ->assertJson([
                'success' => true,
                'synced' => [
                    [
                        'client_uuid' => $uuid,
                        'status' => 'already_synced',
                    ],
                ],
            ]);

        $this->assertEquals(1, Order::where('client_uuid', $uuid)->count());
    }

    public function test_offline_dine_in_sync_links_table(): void
    {
        $category = Category::create(['name' => 'Burgers', 'slug' => 'burgers', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Beef Burger',
            'name_ur' => 'بیف برگر',
            'code' => 'BEEF-01',
            'category_id' => $category->id,
            'sale_price' => 600.00,
            'cost_price' => 300.00,
            'is_active' => true,
        ]);

        $table = RestaurantTable::first();

        $uuid = (string) Str::uuid();
        $payload = [
            'orders' => [
                [
                    'client_uuid' => $uuid,
                    'offline_order_number' => 'OFF-DIN-260908-0003',
                    'order_type' => 'DINE_IN',
                    'table_id' => $table->id,
                    'table_name' => $table->name,
                    'customer_name' => 'Usman Tariq',
                    'customer_phone' => '03331112233',
                    'subtotal' => 600.00,
                    'grand_total' => 600.00,
                    'is_finalized' => false,
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'name_ur' => $product->name_ur,
                            'price' => 600.00,
                            'qty' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('pos.api.sync'), $payload);

        $response->assertOk()->assertJson(['synced_count' => 1]);

        $order = Order::where('client_uuid', $uuid)->first();
        $this->assertNotNull($order);
        $this->assertEquals('DINE_IN', $order->order_type);
        $this->assertEquals($table->id, $order->table_id);
        $this->assertEquals($table->name, $order->table_name);
    }
}
