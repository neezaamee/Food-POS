<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DayClose;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use App\Services\Sales\OrderService;
use App\Services\Sales\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayCloseAndCancellationRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_closed_shift_order_cannot_be_cancelled(): void
    {
        $manager = User::whereIn('role', ['manager', 'admin', 'super-admin'])->first()
            ?? User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager);

        // Open shift, then close it
        $shiftService = app(CashShiftService::class);
        $shift = $shiftService->openShift(2000.00, 'Shift to close');
        $shiftService->closeShift($shift, 2000.00, 'Closing shift');

        $this->assertFalse($shift->fresh()->isOpen());

        // Create an order belonging to this closed shift
        $order = Order::create([
            'order_number' => 'TAK-2026999901',
            'order_type' => 'TAKEAWAY',
            'order_status' => 'draft',
            'payment_status' => 'unpaid',
            'customer_name' => 'Test Customer',
            'customer_phone' => '03001234567',
            'subtotal' => 500.00,
            'grand_total' => 500.00,
            'paid_amount' => 0.00,
            'balance_amount' => 500.00,
            'user_id' => $manager->id,
            'cash_shift_id' => $shift->id,
        ]);

        $response = $this->post(route('orders.cancel', $order->id), [
            'reason' => 'Customer changed mind',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('cannot be cancelled after the shift is closed', session('error'));
        $this->assertNotEquals('cancelled', $order->fresh()->order_status);
    }

    public function test_cashier_or_staff_cannot_cancel_order(): void
    {
        $cashier = User::where('role', 'cashier')->first()
            ?? User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier);
        $this->assertFalse($cashier->canCancelOrder());

        $shift = app(CashShiftService::class)->openShift(1000.00);

        $order = Order::create([
            'order_number' => 'TAK-2026999902',
            'order_type' => 'TAKEAWAY',
            'order_status' => 'draft',
            'payment_status' => 'unpaid',
            'customer_name' => 'Test Customer',
            'subtotal' => 300.00,
            'grand_total' => 300.00,
            'user_id' => $cashier->id,
            'cash_shift_id' => $shift->id,
        ]);

        $response = $this->post(route('orders.cancel', $order->id), [
            'reason' => 'Attempt by cashier',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Only a Manager, Owner, or Super Administrator', session('error'));
        $this->assertNotEquals('cancelled', $order->fresh()->order_status);
    }

    public function test_manager_can_cancel_finalized_order_in_open_shift_with_full_reversal(): void
    {
        $manager = User::whereIn('role', ['manager', 'admin', 'super-admin'])->first()
            ?? User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager);

        $shift = app(CashShiftService::class)->openShift(5000.00);

        $category = Category::firstOrCreate(['name' => 'Burgers'], ['slug' => 'burgers', 'is_active' => true]);
        $product = Product::firstOrCreate(
            ['code' => 'BUR-CAN-01'],
            [
                'name' => 'Cancel Test Burger',
                'sku' => 'BUR-CAN-01',
                'category_id' => $category->id,
                'sale_price' => 600.00,
                'cost_price' => 250.00,
                'current_stock' => 20.00,
                'is_active' => true,
            ]
        );

        $initialStock = (float) $product->current_stock;

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'order_type' => 'TAKEAWAY',
            'customer_name' => 'Fahad Mustafa',
            'customer_phone' => '03007654321',
            'cash_shift_id' => $shift->id,
        ]);

        $orderService->addItem($order, $product, 2); // 2 x 600 = 1200

        // Pay in cash and finalize
        app(PaymentService::class)->recordPayment($order, 'cash', 1200.00);
        $orderService->finalizeOrder($order);

        $this->assertEquals('completed', $order->fresh()->order_status);
        $this->assertEquals($initialStock - 2, (float) $product->fresh()->current_stock);
        $this->assertEquals(1200.00, (float) $shift->fresh()->cash_sales);

        // Cancel the order as manager (Restock to inventory)
        $response = $this->post(route('orders.cancel', $order->id), [
            'reason' => 'Customer rejected delivery upon arrival',
            'is_waste' => 0,
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals('cancelled', $order->fresh()->order_status);

        // Verify inventory is restocked
        $this->assertEquals($initialStock, (float) $product->fresh()->current_stock);

        // Verify cash shift deducted the refunded sales
        $this->assertEquals(0.00, (float) $shift->fresh()->cash_sales);

        // Verify accounting double entry reversal exists
        $reversalEntry = JournalEntry::where('reference_type', 'Order')
            ->where('reference_id', $order->id)
            ->where('voucher_type', 'sale_return')
            ->first();

        $this->assertNotNull($reversalEntry);
        $this->assertEquals(1200.00 + (250 * 2), (float) $reversalEntry->total_debit);
    }

    public function test_catalog_actions_dropdown_and_edit_data_api(): void
    {
        $admin = User::whereIn('role', ['admin', 'super-admin'])->first()
            ?? User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin);

        $product = Product::firstOrCreate(
            ['code' => 'TEST-DROPDOWN-01'],
            [
                'name' => 'Dropdown Action Dish',
                'sku' => 'TEST-DROPDOWN-01',
                'category_id' => Category::first()->id,
                'sale_price' => 750.00,
                'cost_price' => 300.00,
                'type' => 'menu_item',
                'is_active' => true,
            ]
        );

        // 1. Check API endpoint returns valid JSON
        $apiResponse = $this->get(route('resources.products.edit-data', $product->id));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonFragment([
            'id' => $product->id,
            'name' => 'Dropdown Action Dish',
        ]);

        // 2. Check catalog index view renders Bootstrap dropdown markup
        $pageResponse = $this->get(route('resources.products.index'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('dropdown-menu dropdown-menu-end');
        $pageResponse->assertSee('Edit Product');
        $pageResponse->assertSee('Recipe (BOM)');
    }

    public function test_day_close_cannot_be_performed_while_a_shift_is_open(): void
    {
        $manager = User::whereIn('role', ['manager', 'admin', 'super-admin'])->first()
            ?? User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager);

        // Open a shift without closing it
        $shiftService = app(CashShiftService::class);
        $openShift = $shiftService->openShift(3000.00);

        $response = $this->post(route('cash.day-close.store'), [
            'date' => now()->toDateString(),
            'notes' => 'Attempting day close prematurely',
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('is still OPEN', session('error'));
        $this->assertDatabaseMissing('day_closes', ['business_date' => now()->toDateString()]);
    }

    public function test_day_close_combines_shift_1_and_shift_2_sales_into_daily_z_report(): void
    {
        $manager = User::whereIn('role', ['manager', 'admin', 'super-admin'])->first()
            ?? User::factory()->create(['role' => 'manager']);

        $this->actingAs($manager);

        $shiftService = app(CashShiftService::class);
        $orderService = app(OrderService::class);
        $paymentService = app(PaymentService::class);
        $category = Category::first();

        $product = Product::firstOrCreate(
            ['code' => 'Z-REP-PIZZA'],
            [
                'name' => 'Z-Report Pizza',
                'category_id' => $category->id,
                'sale_price' => 1000.00,
                'cost_price' => 400.00,
                'is_active' => true,
            ]
        );

        // Shift 1: Cashier 1 sells 1 pizza = Rs. 1000
        $shift1 = $shiftService->openShift(2000.00, 'Shift 1 Morning');
        $order1 = $orderService->createOrder(['order_type' => 'TAKEAWAY', 'cash_shift_id' => $shift1->id]);
        $orderService->addItem($order1, $product, 1);
        $paymentService->recordPayment($order1, 'cash', 1000.00);
        $orderService->finalizeOrder($order1);
        $shiftService->closeShift($shift1, 3000.00, 'Shift 1 Closed on time');

        // Shift 2: Cashier 2 sells 2 pizzas = Rs. 2000
        $shift2 = $shiftService->openShift(2000.00, 'Shift 2 Evening');
        $order2 = $orderService->createOrder(['order_type' => 'TAKEAWAY', 'cash_shift_id' => $shift2->id]);
        $orderService->addItem($order2, $product, 2);
        $paymentService->recordPayment($order2, 'cash', 2000.00);
        $orderService->finalizeOrder($order2);
        $shiftService->closeShift($shift2, 4000.00, 'Shift 2 Closed on time');

        // Execute Day Close (Combines Shift 1 + Shift 2)
        $today = now()->toDateString();
        $response = $this->post(route('cash.day-close.store'), [
            'date' => $today,
            'notes' => 'All shifts audited. Shift 1 (1000) + Shift 2 (2000) reconciled.',
        ]);

        $dayClose = DayClose::whereDate('business_date', $today)->first();
        $this->assertNotNull($dayClose);

        $response->assertRedirect(route('cash.day-close.z-report', $dayClose->id));

        // Combined Net Sales must equal 1000 + 2000 = 3000
        $this->assertEquals(3000.00, (float) $dayClose->net_sales);
        $this->assertEquals(3000.00, (float) $dayClose->cash_sales);
        $this->assertEquals(2, $dayClose->total_shifts_count);
        $this->assertEquals(2, $dayClose->total_orders_count);
        $this->assertEquals(4000.00, (float) $dayClose->opening_cash_total); // 2000 + 2000
        $this->assertEquals(7000.00, (float) $dayClose->actual_cash_total);  // 3000 + 4000

        // View Z-Report
        $zReportResponse = $this->get(route('cash.day-close.z-report', $dayClose->id));
        $zReportResponse->assertStatus(200);
        $zReportResponse->assertSee('DAILY Z-REPORT');
        $zReportResponse->assertSee('Rs. 3,000.00');
        $zReportResponse->assertSee('Shift 1 Morning');
        $zReportResponse->assertSee('Shift 2 Evening');
    }
}
