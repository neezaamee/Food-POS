<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Category;
use App\Models\DeliveryArea;
use App\Models\DeliveryRider;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\TableSection;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use App\Services\Restaurant\TableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosModificationsAndRiderReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        app(CashShiftService::class)->openShift(5000.00, 'Shift 1', $this->user->id);
    }

    public function test_pos_clear_order_resets_all_fields_and_cart(): void
    {
        $category = Category::create(['name' => 'Fast Food', 'slug' => 'fast-food', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Pizza Slice',
            'code' => 'PIZ-01',
            'category_id' => $category->id,
            'sale_price' => 350.00,
            'cost_price' => 150.00,
            'is_active' => true,
        ]);

        Livewire::test(PosScreen::class)
            ->set('customerName', 'Babar Azam')
            ->set('customerPhone', '03009988776')
            ->set('customerAddress', 'Street 4, D-Ground')
            ->set('orderNotes', 'Extra cheese')
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('clearOrder')
            ->assertCount('cart', 0)
            ->assertSet('customerName', '')
            ->assertSet('customerPhone', '')
            ->assertSet('customerAddress', '')
            ->assertSet('orderNotes', '');
    }

    public function test_pos_cancel_open_order_updates_status_and_releases_table(): void
    {
        $section = TableSection::create(['name' => 'Main Hall', 'is_active' => true]);
        $table = RestaurantTable::create([
            'table_number' => 'T-01',
            'name' => 'Table 1',
            'section_id' => $section->id,
            'capacity' => 4,
            'status' => 'available',
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Fast Food', 'slug' => 'fast-food-2', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Burger Combo',
            'code' => 'CMB-01',
            'category_id' => $category->id,
            'sale_price' => 500.00,
            'cost_price' => 200.00,
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'DIN-2026000099',
            'order_type' => 'DINE_IN',
            'order_status' => 'draft',
            'table_id' => $table->id,
            'table_name' => $table->name,
            'customer_name' => 'Shaheen Afridi',
            'customer_phone' => '03112233445',
            'grand_total' => 500.00,
            'user_id' => $this->user->id,
        ]);
        app(TableService::class)->occupyTable($table, $order);
        $this->assertEquals('occupied', $table->fresh()->status);

        Livewire::test(PosScreen::class)
            ->call('cancelOpenOrder', $order->id)
            ->assertSet('notificationType', 'warning');

        $this->assertEquals('cancelled', $order->fresh()->order_status);
        $this->assertEquals('available', $table->fresh()->status);
    }

    public function test_product_catalog_delete_destroys_product_and_variants(): void
    {
        $category = Category::create(['name' => 'Beverages', 'slug' => 'beverages', 'is_active' => true]);
        $parent = Product::create([
            'name' => 'Fresh Lemonade',
            'code' => 'LEM-01',
            'category_id' => $category->id,
            'sale_price' => 200.00,
            'is_active' => true,
            'has_variants' => true,
        ]);

        $variant = Product::create([
            'parent_id' => $parent->id,
            'name' => 'Fresh Lemonade (Large)',
            'variation_name' => 'Large',
            'code' => 'LEM-01-L',
            'category_id' => $category->id,
            'sale_price' => 300.00,
            'is_active' => true,
        ]);

        $response = $this->delete(route('resources.products.destroy', $parent->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $parent->id]);
        $this->assertDatabaseMissing('products', ['id' => $variant->id]);
    }

    public function test_delivery_and_rider_km_report_calculates_and_displays_mileage(): void
    {
        $area = DeliveryArea::create([
            'name' => 'Model Town',
            'code' => 'MDL',
            'delivery_charge' => 150.00,
            'estimated_distance_km' => 6.5,
            'is_active' => true,
        ]);

        $rider = DeliveryRider::create([
            'name' => 'Usman Khan',
            'mobile' => '03017778899',
            'employee_id' => 'RDR-901',
            'vehicle_type' => 'Honda 125',
            'vehicle_number' => 'FSD-1234',
            'status' => 'available',
        ]);

        $order = Order::create([
            'order_number' => 'DEL-2026000088',
            'order_type' => 'DELIVERY',
            'order_status' => 'delivered',
            'customer_name' => 'Naseem Shah',
            'customer_phone' => '03221144556',
            'delivery_area_id' => $area->id,
            'delivery_rider_id' => $rider->id,
            'delivery_charge' => 150.00,
            'delivery_distance_km' => 6.5,
            'rider_starting_km' => 1000.0,
            'rider_ending_km' => 1006.5,
            'rider_total_km' => 6.5,
            'grand_total' => 1200.00,
            'finalized_at' => now(),
            'user_id' => $this->user->id,
        ]);

        $response = $this->get(route('reports.delivery'));
        $response->assertOk();
        $response->assertSee('DEL-2026000088');
        $response->assertSee('Usman Khan');
        $response->assertSee('6.5 KM');
        $response->assertSee('Total Kilometers Logged');
    }

    public function test_rider_order_mileage_can_be_updated_via_endpoint(): void
    {
        $area = DeliveryArea::create([
            'name' => 'Gulberg',
            'code' => 'GLB',
            'delivery_charge' => 100.00,
            'estimated_distance_km' => 4.0,
            'is_active' => true,
        ]);

        $rider = DeliveryRider::create([
            'name' => 'Fakhar Zaman',
            'mobile' => '03445566778',
            'employee_id' => 'RDR-902',
            'vehicle_type' => 'Yamaha YBR',
            'vehicle_number' => 'LHR-5678',
            'status' => 'available',
        ]);

        $order = Order::create([
            'order_number' => 'DEL-2026000077',
            'order_type' => 'DELIVERY',
            'order_status' => 'completed',
            'delivery_area_id' => $area->id,
            'delivery_charge' => 100.00,
            'grand_total' => 800.00,
            'finalized_at' => now(),
            'user_id' => $this->user->id,
        ]);

        $response = $this->post(route('restaurant.delivery.order.mileage', $order->id), [
            'delivery_rider_id' => $rider->id,
            'rider_starting_km' => 500.0,
            'rider_ending_km' => 507.5,
            'rider_total_km' => 7.5,
        ]);

        $response->assertSessionHas('success');
        $fresh = $order->fresh();
        $this->assertEquals($rider->id, $fresh->delivery_rider_id);
        $this->assertEquals(500.0, (float) $fresh->rider_starting_km);
        $this->assertEquals(507.5, (float) $fresh->rider_ending_km);
        $this->assertEquals(7.5, (float) $fresh->rider_total_km);
    }

    public function test_dine_in_order_cannot_be_saved_without_table_selection(): void
    {
        $category = Category::create(['name' => 'Burgers', 'slug' => 'burgers', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Beef Burger',
            'code' => 'BEEF-01',
            'category_id' => $category->id,
            'sale_price' => 600.00,
            'cost_price' => 350.00,
            'is_active' => true,
        ]);

        // Attempting to save Dine-In order without table should fail and not create order
        Livewire::test(PosScreen::class)
            ->call('setOrderType', 'DINE_IN')
            ->set('customerName', 'Ali Raza')
            ->set('customerPhone', '03001234567')
            ->call('addToCart', $product->id)
            ->call('saveOpenOrder')
            ->assertSet('notificationType', 'danger')
            ->assertSet('notificationMessage', 'Table selection is mandatory for Dine-In orders. Please select a table.');

        $this->assertEquals(0, Order::where('order_type', 'DINE_IN')->count());
    }

    public function test_dine_in_order_cannot_be_checked_out_without_table_selection(): void
    {
        $category = Category::create(['name' => 'Drinks', 'slug' => 'drinks', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Lemonade',
            'code' => 'LEM-01',
            'category_id' => $category->id,
            'sale_price' => 200.00,
            'cost_price' => 80.00,
            'is_active' => true,
        ]);

        Livewire::test(PosScreen::class)
            ->call('setOrderType', 'DINE_IN')
            ->set('customerName', 'Ali Raza')
            ->set('customerPhone', '03001234567')
            ->call('addToCart', $product->id)
            ->call('openCheckout')
            ->assertSet('showCheckoutModal', false)
            ->assertSet('notificationType', 'danger');

        $this->assertEquals(0, Order::where('order_type', 'DINE_IN')->count());
    }

    public function test_dine_in_order_saves_and_occupies_table_when_table_is_selected(): void
    {
        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Cheese Pizza',
            'code' => 'PZ-01',
            'category_id' => $category->id,
            'sale_price' => 1200.00,
            'cost_price' => 600.00,
            'is_active' => true,
        ]);

        $section = TableSection::create(['name' => 'Main Hall']);
        $table = RestaurantTable::create([
            'table_number' => 'T-99',
            'name' => 'VIP Table 99',
            'section_id' => $section->id,
            'capacity' => 6,
            'status' => 'available',
        ]);

        Livewire::test(PosScreen::class)
            ->call('setOrderType', 'DINE_IN')
            ->call('selectTable', $table->id)
            ->set('customerName', 'Babar Azam')
            ->set('customerPhone', '03009998877')
            ->call('addToCart', $product->id)
            ->call('saveOpenOrder');

        $order = Order::where('order_type', 'DINE_IN')->first();
        $this->assertNotNull($order);
        $this->assertEquals($table->id, $order->table_id);
        $this->assertEquals($table->name, $order->table_name);
        $this->assertTrue($table->fresh()->isOccupied());
    }

    public function test_orders_index_page_loads_with_clean_pagination(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Order::create([
                'order_number' => 'TEST-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'order_type' => 'TAKEAWAY',
                'order_status' => 'completed',
                'payment_status' => 'paid',
                'customer_name' => 'Test Customer '.$i,
                'subtotal' => 100.00,
                'grand_total' => 100.00,
                'paid_amount' => 100.00,
                'finalized_at' => now(),
                'user_id' => $this->user->id,
            ]);
        }

        $response = $this->get(route('orders.index'));
        $response->assertOk();
        $response->assertSee('Sale Orders & Invoices');
        $response->assertSee('pagination');
    }
}
