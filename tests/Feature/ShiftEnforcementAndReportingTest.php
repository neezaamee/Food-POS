<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\CashShift;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\TableSection;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use App\Services\Sales\OrderService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftEnforcementAndReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_order_can_be_punched_until_shift_is_open(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $category = Category::firstOrCreate(['name' => 'Fast Food'], ['slug' => 'fast-food', 'is_active' => true]);
        $product = Product::firstOrCreate(
            ['code' => 'BUR-TEST'],
            [
                'name' => 'Test Burger',
                'sku' => 'BUR-TEST',
                'category_id' => $category->id,
                'sale_price' => 500.00,
                'cost_price' => 300.00,
                'is_active' => true,
            ]
        );

        // 1. With NO active shift: adding to cart is blocked and triggers open shift modal
        Livewire::test(PosScreen::class)
            ->call('addToCart', $product->id)
            ->assertCount('cart', 0)
            ->assertSet('showOpenShiftModal', true);

        // 2. Open shift directly from Live POS
        Livewire::test(PosScreen::class)
            ->set('posOpeningCash', 5000.00)
            ->call('openShiftFromPos')
            ->assertSet('showOpenShiftModal', false);

        $this->assertDatabaseHas('cash_shifts', [
            'user_id' => $user->id,
            'status' => 'open',
            'opening_cash' => 5000.00,
        ]);

        // 3. Now that shift is open: adding to cart and punching succeeds
        Livewire::test(PosScreen::class)
            ->set('customerName', 'Shaheen Afridi')
            ->set('customerPhone', '03005555555')
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('saveOpenOrder')
            ->assertCount('cart', 0); // Cart resets for next order

        // Verify order has cash_shift_id stamped
        $order = Order::latest('id')->first();
        $this->assertNotNull($order->cash_shift_id);
    }

    public function test_shift_cannot_close_until_all_active_orders_are_paid(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $shiftService = app(CashShiftService::class);
        $shift = $shiftService->openShift(5000.00, 'Test Shift', $user->id);

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
            'customer_name' => 'Unpaid Customer',
            'user_id' => $user->id,
            'cash_shift_id' => $shift->id,
        ]);

        // Order is currently unpaid
        $this->assertEquals('unpaid', $order->payment_status);

        // Trying to close shift must throw an Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot close Shift');
        $shiftService->closeShift($shift, 5000.00, 'Handover', $user->id);
    }

    public function test_shift_closes_successfully_after_all_orders_are_paid(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $shiftService = app(CashShiftService::class);
        $shift = $shiftService->openShift(5000.00, 'Test Shift', $user->id);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'order_type' => 'TAKEAWAY',
            'customer_name' => 'Paid Customer',
            'user_id' => $user->id,
            'cash_shift_id' => $shift->id,
        ]);

        // Mark order as paid
        $order->payment_status = 'paid';
        $order->paid_amount = 500.00;
        $order->balance_amount = 0.00;
        $order->save();

        // Closing shift now succeeds
        $closedShift = $shiftService->closeShift($shift, 5000.00, 'Shift Complete', $user->id);
        $this->assertEquals('closed', $closedShift->status);
        $this->assertNotNull($closedShift->closed_at);
    }

    public function test_day_wise_and_shift_wise_sales_reports(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $today = now()->toDateString();

        // Create Shift 1 (Lunch)
        $shift1 = CashShift::create([
            'user_id' => $user->id,
            'opened_at' => now()->startOfDay()->addHours(11),
            'closed_at' => now()->startOfDay()->addHours(16),
            'opening_cash' => 5000.00,
            'cash_sales' => 12000.00,
            'expected_cash' => 17000.00,
            'actual_cash' => 17000.00,
            'difference' => 0.00,
            'status' => 'closed',
            'notes' => 'Lunch Shift',
        ]);

        // Create Shift 2 (Dinner)
        $shift2 = CashShift::create([
            'user_id' => $user->id,
            'opened_at' => now()->startOfDay()->addHours(17),
            'closed_at' => now()->startOfDay()->addHours(23),
            'opening_cash' => 5000.00,
            'cash_sales' => 18000.00,
            'expected_cash' => 23000.00,
            'actual_cash' => 23000.00,
            'difference' => 0.00,
            'status' => 'closed',
            'notes' => 'Dinner Shift',
        ]);

        // Create Orders for Shift 1
        Order::create([
            'order_number' => 'TAK-2026-LUNCH',
            'order_type' => 'TAKEAWAY',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'grand_total' => 12000.00,
            'paid_amount' => 12000.00,
            'cash_shift_id' => $shift1->id,
            'user_id' => $user->id,
            'created_at' => now()->startOfDay()->addHours(12),
            'finalized_at' => now()->startOfDay()->addHours(12),
        ]);

        // Create Orders for Shift 2
        Order::create([
            'order_number' => 'DIN-2026-DINNER',
            'order_type' => 'DINE_IN',
            'order_status' => 'completed',
            'payment_status' => 'paid',
            'grand_total' => 18000.00,
            'paid_amount' => 18000.00,
            'cash_shift_id' => $shift2->id,
            'user_id' => $user->id,
            'created_at' => now()->startOfDay()->addHours(19),
            'finalized_at' => now()->startOfDay()->addHours(19),
        ]);

        // Test Day-Wise Sales Report: Combines lunch and dinner into one day total (12000 + 18000 = 30000)
        $dailyResponse = $this->get(route('reports.daily-sales', [
            'from_date' => $today,
            'to_date' => $today,
        ]));
        $dailyResponse->assertOk();
        $dailyResponse->assertSee('Rs. 30,000.00'); // Combined Day's Sales
        $dailyResponse->assertSee('Shift #'.$shift1->id);
        $dailyResponse->assertSee('Shift #'.$shift2->id);

        // Test Shift-Wise Sales Report: Lists shifts separately
        $shiftResponse = $this->get(route('reports.shift-sales'));
        $shiftResponse->assertOk();
        $shiftResponse->assertSee('Shift #'.$shift1->id);
        $shiftResponse->assertSee('Shift #'.$shift2->id);
        $shiftResponse->assertSee('Rs. 12,000.00');
        $shiftResponse->assertSee('Rs. 18,000.00');
    }
}
