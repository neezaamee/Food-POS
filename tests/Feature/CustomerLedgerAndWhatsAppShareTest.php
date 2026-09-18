<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CustomerLedgerAndWhatsAppShareTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);
    }

    public function test_customer_model_balance_accessors_and_sync(): void
    {
        $customer = Customer::create([
            'name' => 'John Doe',
            'mobile' => '03001234567',
            'opening_balance' => 1500.00,
            'current_balance' => 0.00,
            'credit_limit' => 50000.00,
        ]);

        // Check backward compatibility accessors
        $this->assertEquals('03001234567', $customer->phone);
        $this->assertEquals(0.00, $customer->balance);

        // Create an order with partial payment (due 1000)
        Order::create([
            'order_number' => 'ORD-TEST-001',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->mobile,
            'order_type' => 'dine_in',
            'subtotal' => 2000.00,
            'tax_amount' => 0.00,
            'discount_amount' => 0.00,
            'delivery_charge' => 0.00,
            'grand_total' => 2000.00,
            'paid_amount' => 1000.00,
            'balance_amount' => 1000.00,
            'payment_status' => 'partial',
            'order_status' => 'completed',
        ]);

        // Calculate and sync
        $expectedBalance = 1500.00 + 1000.00; // 2500.00
        $this->assertEquals($expectedBalance, $customer->calculateOutstandingBalance());

        $customer->syncBalance();
        $customer->refresh();
        $this->assertEquals($expectedBalance, (float) $customer->current_balance);
        $this->assertEquals($expectedBalance, (float) $customer->balance);
    }

    public function test_customer_ledger_displays_opening_balance_and_running_remaining_balance(): void
    {
        $customer = Customer::create([
            'name' => 'Sara Khan',
            'mobile' => '03219876543',
            'opening_balance' => 1000.00,
            'current_balance' => 1000.00,
        ]);

        // Order 1: 2500 total, 1500 paid -> adds 1000 to remaining
        $order1 = Order::create([
            'order_number' => 'ORD-101',
            'customer_id' => $customer->id,
            'order_type' => 'takeaway',
            'subtotal' => 2500.00,
            'grand_total' => 2500.00,
            'paid_amount' => 1500.00,
            'balance_amount' => 1000.00,
            'payment_status' => 'partial',
            'order_status' => 'completed',
        ]);
        $order1->created_at = Carbon::now()->subDays(3);
        $order1->save();

        // Order 2: 1200 total, 1200 paid -> adds 0 to remaining
        $order2 = Order::create([
            'order_number' => 'ORD-102',
            'customer_id' => $customer->id,
            'order_type' => 'delivery',
            'subtotal' => 1200.00,
            'grand_total' => 1200.00,
            'paid_amount' => 1200.00,
            'balance_amount' => 0.00,
            'payment_status' => 'paid',
            'order_status' => 'completed',
        ]);
        $order2->created_at = Carbon::now()->subDays(2);
        $order2->save();

        // Sale Return: 300 refunded -> subtracts 300 from remaining
        $return = SaleReturn::create([
            'return_number' => 'RET-001',
            'order_id' => $order1->id,
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'reason' => 'Customer request',
            'subtotal' => 300.00,
            'tax_amount' => 0.00,
            'grand_total' => 300.00,
            'refund_method' => 'cash',
            'refund_status' => 'completed',
        ]);
        $return->created_at = Carbon::now()->subDays(1);
        $return->save();

        $response = $this->get(route('reports.customer-ledger', ['customer_id' => $customer->id]));

        $response->assertOk();
        $response->assertViewHas('selectedCustomer');
        $response->assertViewHas('summary');
        $response->assertViewHas('ledgerEntries');

        $summary = $response->viewData('summary');
        $this->assertEquals(1000.00, $summary['opening_balance']);
        $this->assertEquals(3700.00, $summary['total_invoiced']);
        $this->assertEquals(2700.00, $summary['total_paid']);
        $this->assertEquals(300.00, $summary['total_returns']);
        // Final Remaining Balance = 1000 (OB) + (3700 - 2700) - 300 = 1700.00
        $this->assertEquals(1700.00, $summary['remaining_balance']);

        // Check running balances in ledger entries
        $entries = $response->viewData('ledgerEntries');
        $this->assertCount(3, $entries);
        $this->assertEquals(2000.00, $entries[0]->remaining_balance); // after order 1 (1000 + 1000)
        $this->assertEquals(2000.00, $entries[1]->remaining_balance); // after order 2 (2000 + 0)
        $this->assertEquals(1700.00, $entries[2]->remaining_balance); // after return (2000 - 300)

        // Verify HTML displays Remaining Balance and Opening Balance
        $response->assertSee('Remaining Balance');
        $response->assertSee('OB-START');
        $response->assertSee('Rs. 1,700.00');
        $response->assertSee('Share via WhatsApp');
    }

    public function test_customer_ledger_date_range_filters_and_recomputes_period_opening_balance(): void
    {
        $customer = Customer::create([
            'name' => 'Ahmed Ali',
            'mobile' => '03331112233',
            'opening_balance' => 500.00,
            'current_balance' => 500.00,
        ]);

        // Prior order (outside date filter): 2000 total, 1000 paid (+1000 net)
        $priorOrder = Order::create([
            'order_number' => 'ORD-PRIOR-01',
            'customer_id' => $customer->id,
            'order_type' => 'dine_in',
            'grand_total' => 2000.00,
            'paid_amount' => 1000.00,
            'balance_amount' => 1000.00,
            'payment_status' => 'partial',
            'order_status' => 'completed',
        ]);
        $priorOrder->created_at = Carbon::now()->subDays(15);
        $priorOrder->save();

        // In-period order: 1500 total, 500 paid (+1000 net)
        $currentOrder = Order::create([
            'order_number' => 'ORD-CURRENT-01',
            'customer_id' => $customer->id,
            'order_type' => 'dine_in',
            'grand_total' => 1500.00,
            'paid_amount' => 500.00,
            'balance_amount' => 1000.00,
            'payment_status' => 'partial',
            'order_status' => 'completed',
        ]);
        $currentOrder->created_at = Carbon::now()->subDays(2);
        $currentOrder->save();

        // Filter from 5 days ago to today
        $response = $this->get(route('reports.customer-ledger', [
            'customer_id' => $customer->id,
            'from_date' => Carbon::now()->subDays(5)->toDateString(),
            'to_date' => Carbon::now()->toDateString(),
        ]));

        $response->assertOk();
        $summary = $response->viewData('summary');

        // Period Opening Balance should include initial 500 + prior unpaid 1000 = 1500
        $this->assertEquals(1500.00, $summary['opening_balance']);
        $this->assertEquals(1500.00, $summary['total_invoiced']);
        $this->assertEquals(500.00, $summary['total_paid']);
        // Final Remaining Balance = 1500 + (1500 - 500) = 2500
        $this->assertEquals(2500.00, $summary['remaining_balance']);

        // Only 1 order entry should be in period
        $entries = $response->viewData('ledgerEntries');
        $this->assertCount(1, $entries);
        $this->assertEquals(2500.00, $entries[0]->remaining_balance);
    }

    public function test_whatsapp_share_checks_connection_first_and_validates_when_disconnected(): void
    {
        $customer = Customer::create([
            'name' => 'Bilal Aslam',
            'mobile' => '03009988776',
            'opening_balance' => 2000.00,
            'current_balance' => 2000.00,
        ]);

        // WhatsAppService will return disconnected by default (as bridge node process is not running in test)
        $response = $this->post(route('reports.customer-ledger.share-whatsapp'), [
            'customer_id' => $customer->id,
            'phone' => '03009988776',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $response->assertSessionHas('whatsapp_fallback_url');

        $errorMessage = session('error');
        $this->assertStringContainsString('WhatsApp integration is not connected', $errorMessage);

        $fallbackUrl = session('whatsapp_fallback_url');
        $this->assertStringContainsString('wa.me/923009988776', $fallbackUrl);
        // The fallback message must contain customer balance
        $this->assertStringContainsString('REMAINING%20BALANCE', $fallbackUrl);
    }

    public function test_whatsapp_share_dispatches_when_connected(): void
    {
        $customer = Customer::create([
            'name' => 'Hamza Tariq',
            'mobile' => '03451234567',
            'opening_balance' => 3500.00,
            'current_balance' => 3500.00,
        ]);

        // Mock WhatsAppService to simulate connected status and successful send
        $mock = Mockery::mock(WhatsAppService::class);
        $mock->shouldReceive('getStatus')->andReturn(['connected' => true, 'state' => 'open']);
        $mock->shouldReceive('sendCustomerBalanceStatement')->once()->andReturn([
            'ok' => true,
            'success' => true,
            'message' => 'Statement sent successfully',
        ]);
        $mock->shouldReceive('formatCustomerBalanceMessage')->andReturn('preview message');
        $this->instance(WhatsAppService::class, $mock);

        $response = $this->post(route('reports.customer-ledger.share-whatsapp'), [
            'customer_id' => $customer->id,
            'phone' => '03451234567',
        ]);

        $response->assertRedirect();
        if (session('error')) {
            $this->fail('Unexpected session error: '.session('error'));
        }
        $response->assertSessionHas('success');
        $this->assertStringContainsString('successfully dispatched via WhatsApp', session('success'));
    }
}
