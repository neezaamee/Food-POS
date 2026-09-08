<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryArea;
use App\Models\DeliveryRider;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use App\Services\Sales\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessConfigurationAndDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_settings_supports_logo_url_and_aliasing(): void
    {
        // When restaurant_name is set, get returns it
        SystemSetting::set('restaurant_name', 'Spice Kingdom POS');
        $this->assertEquals('Spice Kingdom POS', SystemSetting::get('restaurant_name'));

        // Default logo URL fallback
        SystemSetting::where('key', 'restaurant_logo')->delete();
        $this->assertStringContainsString('logo.webp', SystemSetting::logoUrl());
    }

    public function test_admin_can_update_settings_and_upload_logo(): void
    {
        $user = User::first() ?? User::factory()->create();

        $file = UploadedFile::fake()->image('test_logo.png', 200, 80);

        $response = $this->actingAs($user)->post(route('admin.settings.update'), [
            'restaurant_name' => 'Royal Dine & Grill',
            'restaurant_address' => '77-Gulberg Main, Lahore',
            'restaurant_phone' => '+92 300 1234567',
            'restaurant_logo' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('Royal Dine & Grill', SystemSetting::get('restaurant_name'));
        $this->assertEquals('77-Gulberg Main, Lahore', SystemSetting::get('restaurant_address'));
        $this->assertNotNull(SystemSetting::get('restaurant_logo'));
        $this->assertStringContainsString('uploads/branding/logo_', SystemSetting::get('restaurant_logo'));
    }

    public function test_delivery_order_creation_with_rider_and_charges(): void
    {
        $user = User::first() ?? User::factory()->create();

        $area = DeliveryArea::firstOrCreate(
            ['name' => 'Model Town'],
            ['code' => 'MT-01', 'delivery_charge' => 150.00, 'is_active' => true]
        );

        $rider = DeliveryRider::firstOrCreate(
            ['name' => 'Ali Rider'],
            ['employee_id' => 'RDR-001', 'mobile' => '03120000000', 'vehicle_type' => 'Bike', 'vehicle_number' => 'LEA-1234', 'status' => 'available', 'is_active' => true]
        );

        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'order_type' => 'DELIVERY',
            'customer_name' => 'Kamran Khan',
            'customer_phone' => '03009998877',
            'customer_address' => 'House 12, Street 4, Model Town',
            'delivery_area_id' => $area->id,
            'delivery_charge' => 150.00,
            'delivery_rider_id' => $rider->id,
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('DELIVERY', $order->order_type);
        $this->assertEquals($area->id, $order->delivery_area_id);
        $this->assertEquals(150.00, (float) $order->delivery_charge);
        $this->assertEquals($rider->id, $order->delivery_rider_id);
        $this->assertEquals('House 12, Street 4, Model Town', $order->customer_address);
    }

    public function test_customer_profile_auto_created_and_linked_on_order(): void
    {
        $user = User::first() ?? User::factory()->create();
        $orderService = app(OrderService::class);

        $testPhone = '0345'.rand(1000000, 9999999);
        $testName = 'Zahid Mehmood';
        $testAddress = 'Phase 6 DHA, Lahore';

        // Order with customer info should auto-create Customer model and attach customer_id
        $order = $orderService->createOrder([
            'order_type' => 'TAKEAWAY',
            'customer_name' => $testName,
            'customer_phone' => $testPhone,
            'customer_address' => $testAddress,
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($order->customer_id);

        $customer = Customer::find($order->customer_id);
        $this->assertNotNull($customer);
        $this->assertEquals($testName, $customer->name);
        $this->assertEquals($testPhone, $customer->mobile);

        // Verify order is linked in Customer Ledger query
        $customerOrders = Order::where('customer_id', $customer->id)->get();
        $this->assertTrue($customerOrders->contains('id', $order->id));
    }

    public function test_invoice_numbers_start_with_tak_din_and_del_prefixes(): void
    {
        $orderService = app(OrderService::class);

        $takeawayNumber = $orderService->generateOrderNumber('TAKEAWAY');
        $this->assertStringStartsWith('TAK-', $takeawayNumber);

        $dineInNumber = $orderService->generateOrderNumber('DINE_IN');
        $this->assertStringStartsWith('DIN-', $dineInNumber);

        $deliveryNumber = $orderService->generateOrderNumber('DELIVERY');
        $this->assertStringStartsWith('DEL-', $deliveryNumber);
    }

    public function test_pos_screen_switches_invoice_prefix_and_masks_mobile(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        Livewire::test(PosScreen::class)
            ->assertSet('orderType', 'TAKEAWAY')
            ->assertSee('TAK-')
            ->call('setOrderType', 'DINE_IN')
            ->assertSet('orderType', 'DINE_IN')
            ->assertSee('DIN-')
            ->call('setOrderType', 'DELIVERY')
            ->assertSet('orderType', 'DELIVERY')
            ->assertSee('DEL-')
            // Test customer mobile masking (clean to 11 digits)
            ->set('customerPhone', '+92 (0300) 667-7991 1234')
            ->assertSet('customerPhone', '03006677991');
    }

    public function test_pos_screen_saves_order_and_refreshes_terminal(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        app(CashShiftService::class)->openShift(5000.00, 'Test Shift', $user->id);

        $category = Category::firstOrCreate(
            ['name' => 'Fast Food'],
            ['slug' => 'fast-food', 'is_active' => true]
        );

        $product = Product::firstOrCreate(
            ['name' => 'Crispy Zinger Burger'],
            [
                'code' => 'ZNG-001',
                'sku' => 'ZNG-001',
                'category_id' => $category->id,
                'sale_price' => 550.00,
                'cost_price' => 350.00,
                'product_type' => 'finished',
                'status' => 'active',
            ]
        );

        // Test saveOpenOrder: Cart has items -> saves draft -> cart is emptied & ready for next
        Livewire::test(PosScreen::class)
            ->set('customerName', 'Babar Azam')
            ->set('customerPhone', '03012345678')
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('saveOpenOrder')
            ->assertCount('cart', 0); // POS terminal refreshed

        // Test saveAndPrintUnpaidBill: Cart has items -> saves draft -> dispatches print-bill -> resets
        Livewire::test(PosScreen::class)
            ->set('customerName', 'Mohammad Rizwan')
            ->set('customerPhone', '03019876543')
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('saveAndPrintUnpaidBill')
            ->assertDispatched('print-bill')
            ->assertCount('cart', 0); // POS terminal refreshed
    }

    public function test_thermal_bill_displays_unpaid_status_for_draft_orders(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'order_type' => 'TAKEAWAY',
            'customer_name' => 'Adnan',
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('orders.thermal', $order->id));
        $response->assertOk();
        $response->assertSee('*** TAKEAWAY - UNPAID BILL ***');
        $response->assertSee('(PAYMENT PENDING)');
        $response->assertSee('PLEASE PAY AT COUNTER / RIDER');
    }
}
