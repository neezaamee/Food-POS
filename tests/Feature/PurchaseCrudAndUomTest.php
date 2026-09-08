<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\FoodPointRestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseCrudAndUomTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $dairyCategory;

    protected Category $groceryCategory;

    protected Unit $unitKg;

    protected Unit $unitLtr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FoodPointRestaurantSeeder::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->dairyCategory = Category::firstOrCreate(['slug' => 'dairy-poultry'], [
            'name' => 'Dairy & Poultry',
            'is_active' => true,
        ]);

        $this->groceryCategory = Category::firstOrCreate(['slug' => 'grocery-spices'], [
            'name' => 'Grocery & Spices',
            'is_active' => true,
        ]);

        $this->unitKg = Unit::firstOrCreate(['code' => 'KG'], ['name' => 'Kilograms', 'is_active' => true]);
        $this->unitLtr = Unit::firstOrCreate(['code' => 'LTR'], ['name' => 'Liters', 'is_active' => true]);
    }

    public function test_can_access_purchase_index_page_with_kpis_and_products(): void
    {
        $response = $this->get(route('inventory.purchases'));

        $response->assertStatus(200);
        $response->assertSee('Purchasable Products');
        $response->assertSee('New Purchase Bill');
    }

    public function test_can_create_multi_item_purchase_with_category_uom_and_stock_increment(): void
    {
        $cheese = Product::create([
            'name' => 'Mozzarella Cheese Block',
            'code' => 'RM-CHEESE-01',
            'sku' => 'RM-CHEESE-01',
            'type' => 'raw_material',
            'category_id' => $this->dairyCategory->id,
            'unit_id' => $this->unitKg->id,
            'cost_price' => 1200,
            'selling_price' => 0,
            'current_stock' => 5,
            'min_stock_alert' => 2,
            'is_active' => true,
        ]);

        $oil = Product::create([
            'name' => 'Cooking Oil Tin',
            'code' => 'RM-OIL-01',
            'sku' => 'RM-OIL-01',
            'type' => 'raw_material',
            'category_id' => $this->groceryCategory->id,
            'unit_id' => $this->unitLtr->id,
            'cost_price' => 550,
            'selling_price' => 0,
            'current_stock' => 10,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $postData = [
            'supplier_name' => 'Al-Madina Wholesale',
            'invoice_number' => 'INV-2026-9901',
            'purchase_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Bulk weekly kitchen supplies',
            'items' => [
                [
                    'product_id' => $cheese->id,
                    'quantity' => 15,
                    'cost_price' => 1250,
                ],
                [
                    'product_id' => $oil->id,
                    'quantity' => 20,
                    'cost_price' => 540,
                ],
            ],
        ];

        $response = $this->post(route('inventory.purchases.store'), $postData);

        $response->assertRedirect(route('inventory.purchases'));
        $response->assertSessionHas('success');

        // Check purchase in database
        $this->assertDatabaseHas('purchases', [
            'supplier_name' => 'Al-Madina Wholesale',
            'invoice_number' => 'INV-2026-9901',
            'payment_method' => 'cash',
            'grand_total' => (15 * 1250) + (20 * 540), // 18750 + 10800 = 29550
        ]);

        $purchase = Purchase::where('invoice_number', 'INV-2026-9901')->first();
        $this->assertNotNull($purchase);
        $this->assertCount(2, $purchase->items);

        // Check helper methods
        $this->assertEquals(35, $purchase->totalQuantity());
        $this->assertStringContainsString('Dairy & Poultry', $purchase->categoriesList());
        $this->assertStringContainsString('Grocery & Spices', $purchase->categoriesList());

        // Check stock increments
        $cheese->refresh();
        $this->assertEquals(20, $cheese->current_stock); // 5 + 15
        $this->assertEquals(1250, (float) $cheese->cost_price);

        $oil->refresh();
        $this->assertEquals(30, $oil->current_stock); // 10 + 20
        $this->assertEquals(540, (float) $oil->cost_price);

        // Check stock movement records
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $cheese->id,
            'movement_type' => 'purchase',
            'quantity' => 15,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $oil->id,
            'movement_type' => 'purchase',
            'quantity' => 20,
        ]);
    }

    public function test_can_view_purchase_details_json(): void
    {
        $product = Product::create([
            'name' => 'Chicken Boneless Meat',
            'code' => 'RM-CHK-01',
            'sku' => 'RM-CHK-01',
            'type' => 'raw_material',
            'category_id' => $this->dairyCategory->id,
            'unit_id' => $this->unitKg->id,
            'cost_price' => 700,
            'selling_price' => 0,
            'current_stock' => 0,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $purchase = Purchase::create([
            'purchase_number' => 'PO-TEST-001',
            'supplier_name' => 'Super Fresh Poultry',
            'invoice_number' => 'SFP-8821',
            'purchase_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'status' => 'received',
            'subtotal' => 14000,
            'grand_total' => 14000,
            'paid_amount' => 14000,
            'user_id' => $this->user->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 20,
            'unit_cost' => 700,
            'subtotal' => 14000,
        ]);

        $response = $this->get(route('inventory.purchases.show', $purchase->id));

        $response->assertStatus(200);
        $response->assertJson([
            'purchase_number' => 'PO-TEST-001',
            'supplier_name' => 'Super Fresh Poultry',
            'invoice_number' => 'SFP-8821',
            'payment_method' => 'bank_transfer',
            'items' => [
                [
                    'product_name' => 'Chicken Boneless Meat',
                    'category_name' => 'Dairy & Poultry',
                    'unit_code' => 'KG',
                    'quantity' => 20,
                    'unit_cost' => 700,
                    'subtotal' => 14000,
                ],
            ],
        ]);
    }

    public function test_can_update_purchase_order_with_stock_reconciliation(): void
    {
        $cheese = Product::create([
            'name' => 'Cheddar Cheese Block',
            'code' => 'RM-CHEDDAR-01',
            'sku' => 'RM-CHEDDAR-01',
            'type' => 'raw_material',
            'category_id' => $this->dairyCategory->id,
            'unit_id' => $this->unitKg->id,
            'cost_price' => 1000,
            'selling_price' => 0,
            'current_stock' => 10,
            'min_stock_alert' => 2,
            'is_active' => true,
        ]);

        // Create initial purchase of 10 KG (current stock goes from 10 to 20)
        $purchase = Purchase::create([
            'purchase_number' => 'PO-UPDATE-001',
            'supplier_name' => 'Original Supplier',
            'purchase_date' => now()->toDateString(),
            'status' => 'received',
            'subtotal' => 10000,
            'grand_total' => 10000,
            'paid_amount' => 10000,
            'user_id' => $this->user->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $cheese->id,
            'quantity' => 10,
            'unit_cost' => 1000,
            'subtotal' => 10000,
        ]);
        $cheese->increment('current_stock', 10);
        $this->assertEquals(20, $cheese->fresh()->current_stock);

        // Update the purchase: change quantity to 25 KG at 1050 each
        $updateData = [
            'supplier_name' => 'Updated Supplier Corp',
            'invoice_number' => 'INV-UPDATED-01',
            'purchase_date' => now()->toDateString(),
            'payment_method' => 'credit',
            'notes' => 'Reconciled quantity',
            'items' => [
                [
                    'product_id' => $cheese->id,
                    'quantity' => 25,
                    'cost_price' => 1050,
                ],
            ],
        ];

        $response = $this->put(route('inventory.purchases.update', $purchase->id), $updateData);

        $response->assertRedirect(route('inventory.purchases'));
        $response->assertSessionHas('success');

        // Verify purchase updated
        $purchase->refresh();
        $this->assertEquals('Updated Supplier Corp', $purchase->supplier_name);
        $this->assertEquals('INV-UPDATED-01', $purchase->invoice_number);
        $this->assertEquals('credit', $purchase->payment_method);
        $this->assertEquals(25 * 1050, (float) $purchase->grand_total);

        // Check stock reconciliation: 20 - 10 (old) + 25 (new) = 35
        $cheese->refresh();
        $this->assertEquals(35, $cheese->current_stock);
        $this->assertEquals(1050, (float) $cheese->cost_price);
    }

    public function test_can_delete_purchase_with_stock_reversal(): void
    {
        $cheese = Product::create([
            'name' => 'Gouda Cheese',
            'code' => 'RM-GOUDA-01',
            'sku' => 'RM-GOUDA-01',
            'type' => 'raw_material',
            'category_id' => $this->dairyCategory->id,
            'unit_id' => $this->unitKg->id,
            'cost_price' => 1500,
            'selling_price' => 0,
            'current_stock' => 15,
            'min_stock_alert' => 2,
            'is_active' => true,
        ]);

        $purchase = Purchase::create([
            'purchase_number' => 'PO-DEL-001',
            'supplier_name' => 'Supplier To Delete',
            'purchase_date' => now()->toDateString(),
            'status' => 'received',
            'subtotal' => 15000,
            'grand_total' => 15000,
            'paid_amount' => 15000,
            'user_id' => $this->user->id,
        ]);

        PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $cheese->id,
            'quantity' => 10,
            'unit_cost' => 1500,
            'subtotal' => 15000,
        ]);

        // Stock was 15, then purchase added 10 -> current_stock is 25
        $cheese->increment('current_stock', 10);
        $this->assertEquals(25, $cheese->fresh()->current_stock);

        // Delete purchase
        $response = $this->delete(route('inventory.purchases.destroy', $purchase->id));

        $response->assertRedirect(route('inventory.purchases'));
        $response->assertSessionHas('success');

        // Check purchase deleted
        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
        $this->assertDatabaseMissing('purchase_items', ['purchase_id' => $purchase->id]);

        // Stock reversed: 25 - 10 = 15
        $cheese->refresh();
        $this->assertEquals(15, $cheese->current_stock);

        // Stock movement recorded
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $cheese->id,
            'movement_type' => 'adjustment',
            'quantity' => -10,
        ]);
    }

    public function test_can_quick_add_purchasable_raw_material_product(): void
    {
        $postData = [
            'name' => 'Black Pepper Powder',
            'name_urdu' => 'کالی مرچ پاؤڈر',
            'category_id' => $this->groceryCategory->id,
            'unit_id' => $this->unitKg->id,
            'cost_price' => 1800,
            'opening_stock' => 5,
            'min_stock_alert' => 1,
        ];

        $response = $this->postJson(route('inventory.purchases.products.store'), $postData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'product' => [
                'name' => 'Black Pepper Powder',
                'category_name' => 'Grocery & Spices',
                'unit_code' => 'KG',
                'cost_price' => 1800,
            ],
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Black Pepper Powder',
            'type' => 'raw_material',
            'category_id' => $this->groceryCategory->id,
            'unit_id' => $this->unitKg->id,
            'current_stock' => 5,
        ]);
    }
}
