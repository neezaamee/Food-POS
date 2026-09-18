<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Account;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\PaymentGatewayTransaction;
use App\Models\Product;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use App\Services\Payments\PaymentManager;
use App\Services\Sales\PaymentService;
use Database\Seeders\FoodPointRestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentGatewayAndDigitalWalletTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FoodPointRestaurantSeeder::class);
        $this->user = User::first() ?? User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);
    }

    public function test_payment_gateway_settings_can_be_saved_and_retrieved(): void
    {
        $response = $this->post(route('admin.payments.settings'), [
            'default_digital_payment_provider' => 'jazzcash',
            'jazzcash_enabled' => '1',
            'jazzcash_environment' => 'sandbox',
            'jazzcash_merchant_id' => 'JC-MERCHANT-999',
            'jazzcash_password' => 'secret_pass_123',
            'jazzcash_integrity_salt' => 'salt_key_777',

            'easypaisa_enabled' => '1',
            'easypaisa_environment' => 'sandbox',
            'easypaisa_store_id' => 'EP-STORE-456',
            'easypaisa_hash_key' => 'ep_hash_secret',
            'easypaisa_account_number' => '03459998877',

            'nayapay_enabled' => '1',
            'nayapay_environment' => 'sandbox',
            'nayapay_client_id' => 'NP-CLIENT-111',

            'raast_enabled' => '1',
            'raast_iban' => 'PK00FOOD999988887777',
            'simulator_enabled' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('jazzcash', SystemSetting::get('default_digital_payment_provider'));
        $this->assertEquals('1', SystemSetting::get('jazzcash_enabled'));
        $this->assertEquals('JC-MERCHANT-999', SystemSetting::get('jazzcash_merchant_id'));
        $this->assertEquals('EP-STORE-456', SystemSetting::get('easypaisa_store_id'));
        $this->assertEquals('PK00FOOD999988887777', SystemSetting::get('raast_iban'));

        // Dashboard view renders configuration cleanly
        $viewResponse = $this->get(route('admin.payments.index'));
        $viewResponse->assertOk();
        $viewResponse->assertSee('JC-MERCHANT-999');
        $viewResponse->assertSee('EP-STORE-456');
    }

    public function test_simulator_driver_processes_instant_sandbox_payments(): void
    {
        $paymentManager = app(PaymentManager::class);

        // Initiate digital payment
        $result = $paymentManager->initiatePayment(
            provider: 'simulator',
            amount: 1250.00,
            mobileNumber: '03001234567',
            orderNumber: 'ORD-TEST-001',
            channel: 'push_request'
        );

        $this->assertTrue($result['ok']);
        $this->assertNotEmpty($result['transaction_id']);
        $txRef = $result['transaction_id'];

        $tx = PaymentGatewayTransaction::where('transaction_reference', $txRef)->first();
        $this->assertNotNull($tx);
        $this->assertEquals('pending_customer', $tx->status);
        $this->assertEquals(1250.00, (float) $tx->amount);

        // Simulate customer approving payment
        $completedTx = $paymentManager->completeTransaction($txRef, [
            'simulated_status' => 'APPROVED',
        ]);

        $this->assertNotNull($completedTx);
        $this->assertEquals('completed', $completedTx->status);
        $this->assertNotNull($completedTx->paid_at);
        $this->assertTrue($completedTx->isSuccess());
    }

    public function test_digital_payment_records_to_bank_account_and_updates_order(): void
    {
        Account::firstOrCreate(
            ['code' => '1120'],
            ['name' => 'Main Bank Account', 'type' => 'asset', 'nature' => 'debit', 'level' => 3]
        );

        $order = Order::create([
            'order_number' => 'ORD-DIGITAL-01',
            'order_type' => 'takeaway',
            'subtotal' => 2400.00,
            'grand_total' => 2400.00,
            'paid_amount' => 0.00,
            'balance_amount' => 2400.00,
            'payment_status' => 'unpaid',
            'order_status' => 'draft',
        ]);

        $paymentService = app(PaymentService::class);
        $payment = $paymentService->recordPayment(
            order: $order,
            method: 'jazzcash',
            amount: 2400.00,
            reference: 'JC-TID-884920'
        );

        $this->assertInstanceOf(OrderPayment::class, $payment);
        $this->assertEquals('jazzcash', $payment->payment_method);
        $this->assertEquals('JC-TID-884920', $payment->payment_reference);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals(2400.00, (float) $order->paid_amount);
        $this->assertEquals(0.00, (float) $order->balance_amount);

        // Verify account mapped to 1120 (Bank/Digital Assets)
        $this->assertNotNull($payment->account);
        $this->assertEquals('1120', $payment->account->code);
    }

    public function test_pos_screen_initiates_digital_wallet_payment_and_simulates_approval(): void
    {
        // Open cash shift so order can be processed
        $shiftService = app(CashShiftService::class);
        $shiftService->openShift($this->user->id, 500.00);

        $category = Category::create(['name' => 'Pizza', 'slug' => 'pizza', 'is_active' => true]);
        $product = Product::create([
            'name' => 'Fajita Pizza Large',
            'code' => 'PIZ-FAJ-01',
            'category_id' => $category->id,
            'sale_price' => 1500.00,
            'cost_price' => 800.00,
            'is_active' => true,
        ]);

        Livewire::test(PosScreen::class)
            ->set('customerName', 'Ali Hassan')
            ->set('customerPhone', '03001234567')
            ->call('addToCart', $product->id)
            ->call('openCheckout')
            ->set('paymentMethod', 'digital')
            ->set('digitalProvider', 'jazzcash')
            ->set('customerWalletMobile', '03001234567')
            ->call('initiateDigitalPayment')
            ->assertSet('digitalPaymentState', 'pending_prompt')
            ->call('simulateDigitalApproval')
            ->assertSet('digitalPaymentState', 'approved')
            ->call('processCheckout')
            ->assertHasNoErrors();

        // Verify order created and payment recorded
        $order = Order::latest()->first();
        $this->assertNotNull($order);
        $this->assertEquals(1500.00, (float) $order->grand_total);
        $this->assertEquals('completed', $order->order_status);

        $payment = $order->payments()->first();
        $this->assertNotNull($payment);
        $this->assertEquals('jazzcash', $payment->payment_method);
        $this->assertStringContainsString('JC', $payment->payment_reference);

        // Verify linked gateway transaction
        $tx = PaymentGatewayTransaction::where('order_id', $order->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals('completed', $tx->status);
    }

    public function test_admin_diagnostic_test_transaction_endpoint(): void
    {
        $response = $this->postJson(route('admin.payments.test'), [
            'provider' => 'easypaisa',
            'mobile' => '03451234567',
            'amount' => 25.00,
            'channel' => 'push_request',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('result.transaction_id'));
    }

    public function test_admin_simulate_action_endpoint(): void
    {
        $paymentManager = app(PaymentManager::class);
        $res = $paymentManager->initiatePayment(
            provider: 'simulator',
            amount: 500.00,
            mobileNumber: '03001112233',
            orderNumber: 'TEST-SIM-01'
        );

        $txRef = $res['transaction_id'];

        $response = $this->postJson(route('admin.payments.simulate'), [
            'transaction_reference' => $txRef,
            'action' => 'approve',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'completed',
        ]);

        $tx = PaymentGatewayTransaction::where('transaction_reference', $txRef)->first();
        $this->assertEquals('completed', $tx->status);
    }

    public function test_thermal_receipt_prints_digital_payment_and_tid(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-RECEIPT-01',
            'order_type' => 'takeaway',
            'subtotal' => 1800.00,
            'grand_total' => 1800.00,
            'paid_amount' => 1800.00,
            'balance_amount' => 0.00,
            'payment_status' => 'paid',
            'order_status' => 'completed',
            'finalized_at' => now(),
        ]);

        $order->payments()->create([
            'payment_method' => 'jazzcash',
            'amount' => 1800.00,
            'payment_reference' => 'JC-RECEIPT-9988',
        ]);

        $response = $this->get(route('orders.thermal', $order->id));
        $response->assertOk();
        $response->assertSee('Paid via Jazzcash');
        $response->assertSee('TID: JC-RECEIPT-9988');
    }
}
