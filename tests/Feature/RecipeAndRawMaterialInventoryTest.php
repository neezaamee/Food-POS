<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Category;
use App\Models\Deal;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\RecipeItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use App\Services\Sales\OrderService;
use Database\Seeders\FoodPointRestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecipeAndRawMaterialInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Category $category;

    protected Unit $unitPcs;

    protected Unit $unitKg;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FoodPointRestaurantSeeder::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->category = Category::firstOrCreate([
            'slug' => 'pizza-fast-food',
        ], [
            'name' => 'Pizza & Fast Food',
            'is_active' => true,
        ]);

        $this->unitPcs = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'is_active' => true]);
        $this->unitKg = Unit::firstOrCreate(['code' => 'KG'], ['name' => 'Kilograms', 'is_active' => true]);
    }

    public function test_can_create_raw_material_and_menu_item_with_type(): void
    {
        $rawMaterial = Product::create([
            'name' => 'Large Pizza Dough Ball',
            'code' => 'TEST-RM-DOUGH',
            'sku' => 'TEST-RM-DOUGH',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 120.00,
            'sale_price' => 0.00,
            'current_stock' => 50,
            'is_active' => true,
        ]);

        $menuItem = Product::create([
            'name' => 'Supreme Cheese Pizza (Large)',
            'code' => 'TEST-FP-PIZZA',
            'sku' => 'TEST-FP-PIZZA',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 0.00,
            'sale_price' => 1500.00,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        $this->assertTrue($rawMaterial->isRawMaterial());
        $this->assertFalse($rawMaterial->isMenuItem());

        $this->assertTrue($menuItem->isMenuItem());
        $this->assertFalse($menuItem->isRawMaterial());
    }

    public function test_recipe_endpoint_and_food_cost_calculation(): void
    {
        $dough = Product::create([
            'name' => 'Large Pizza Dough Ball',
            'code' => 'TEST-RM-DOUGH',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 120.00,
            'sale_price' => 0.00,
            'current_stock' => 50,
            'is_active' => true,
        ]);

        $cheese = Product::create([
            'name' => 'Mozzarella Cheese',
            'code' => 'TEST-RM-CHEESE',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitKg->id,
            'cost_price' => 1000.00, // Rs. 1000 per KG
            'sale_price' => 0.00,
            'current_stock' => 20,
            'is_active' => true,
        ]);

        $pizza = Product::create([
            'name' => 'Supreme Cheese Pizza (Large)',
            'code' => 'TEST-FP-PIZZA',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 0.00,
            'sale_price' => 1500.00,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        // Save recipe via HTTP POST: 1 Dough (120) + 0.350 KG Cheese (350) = Rs. 470 Food Cost
        $response = $this->post(route('resources.products.recipe.save', $pizza->id), [
            'ingredients' => [
                ['ingredient_id' => $dough->id, 'quantity' => 1.0, 'notes' => 'Base dough'],
                ['ingredient_id' => $cheese->id, 'quantity' => 0.35, 'notes' => 'Topping cheese'],
            ],
        ]);

        $response->assertSessionHas('success');

        $pizza->refresh();
        $this->assertTrue($pizza->hasRecipe());
        $this->assertEquals(2, $pizza->recipeItems()->count());
        $this->assertEquals(470.00, (float) $pizza->calculateRecipeCost());
        $this->assertEquals(470.00, (float) $pizza->cost_price); // Auto-updated

        // Expected Profit Margin = round((1500 - 470) / 1500 * 100, 1) = 68.7%
        $this->assertEquals(68.7, $pizza->calculateProfitMargin());

        // Verify JSON recipe endpoint
        $jsonResponse = $this->getJson(route('resources.products.recipe', $pizza->id));
        $jsonResponse->assertOk();
        $jsonResponse->assertJsonFragment([
            'recipe_cost' => 470.00,
        ]);
    }

    public function test_purchases_allow_raw_material_and_reject_prepared_menu_item(): void
    {
        $rawMaterial = Product::create([
            'name' => 'Large Pizza Dough Ball',
            'code' => 'TEST-RM-DOUGH',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 120.00,
            'sale_price' => 0.00,
            'current_stock' => 10,
            'is_active' => true,
        ]);

        $menuItem = Product::create([
            'name' => 'Supreme Cheese Pizza (Large)',
            'code' => 'TEST-FP-PIZZA',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 470.00,
            'sale_price' => 1500.00,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        // Purchasing menu items is accepted
        $response = $this->post(route('inventory.purchases.store'), [
            'supplier_name' => 'Kitchen Supplier',
            'purchase_date' => now()->toDateString(),
            'product_id' => $menuItem->id,
            'quantity' => 10,
            'unit_cost' => 470.00,
        ]);
        $response->assertSessionHas('success');
        $menuItem->refresh();
        $this->assertEquals(10, (float) $menuItem->current_stock);

        // Purchasing raw materials with decimal quantity is accepted
        $responseValid = $this->post(route('inventory.purchases.store'), [
            'supplier_name' => 'Dough Factory',
            'purchase_date' => now()->toDateString(),
            'product_id' => $rawMaterial->id,
            'quantity' => 25.5,
            'unit_cost' => 125.00,
        ]);
        $responseValid->assertSessionHas('success');

        $rawMaterial->refresh();
        $this->assertEquals(35.5, (float) $rawMaterial->current_stock);
        $this->assertEquals(125.00, (float) $rawMaterial->cost_price); // Updated to latest purchase cost

        // Check StockMovement
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $rawMaterial->id,
            'movement_type' => 'purchase',
            'quantity' => 25.5,
            'balance_after' => 35.5,
        ]);
    }

    public function test_pos_terminal_excludes_raw_materials_from_screen_and_cart(): void
    {
        app(CashShiftService::class)->openShift(5000.00, 'Test Shift', $this->user->id);

        $rawMaterial = Product::create([
            'name' => 'Large Pizza Dough Ball',
            'code' => 'TEST-RM-DOUGH',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 120.00,
            'sale_price' => 0.00,
            'current_stock' => 50,
            'is_active' => true,
        ]);

        $menuItem = Product::create([
            'name' => 'Supreme Cheese Pizza (Large)',
            'code' => 'TEST-FP-PIZZA',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 470.00,
            'sale_price' => 1500.00,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        $livewire = Livewire::test(PosScreen::class);

        // Verify products passed to render view DO NOT include raw materials
        $renderedProducts = $livewire->viewData('products');
        $this->assertFalse($renderedProducts->contains('id', $rawMaterial->id));
        $this->assertTrue($renderedProducts->contains('id', $menuItem->id));

        // Attempting to add raw material directly to cart is ignored
        $livewire->call('addToCart', $rawMaterial->id)
            ->assertCount('cart', 0);

        // Attempting to enter raw material code warns the cashier and does not add it
        $livewire->set('productCodeInput', 'TEST-RM-DOUGH')
            ->call('handleProductCodeEnter')
            ->assertCount('cart', 0);

        // Adding menu item succeeds
        $livewire->set('productCodeInput', 'TEST-FP-PIZZA')
            ->call('handleProductCodeEnter')
            ->assertCount('cart', 1);
    }

    public function test_finalizing_order_deducts_raw_materials_according_to_recipe(): void
    {
        app(CashShiftService::class)->openShift(5000.00, 'Test Shift', $this->user->id);

        $dough = Product::create([
            'name' => 'Large Pizza Dough Ball',
            'code' => 'TEST-RM-DOUGH',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 120.00,
            'sale_price' => 0.00,
            'current_stock' => 10.0, // 10 dough balls in stock
            'is_active' => true,
        ]);

        $cheese = Product::create([
            'name' => 'Mozzarella Cheese',
            'code' => 'TEST-RM-CHEESE',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitKg->id,
            'cost_price' => 1000.00,
            'sale_price' => 0.00,
            'current_stock' => 5.0, // 5.0 KG in stock
            'is_active' => true,
        ]);

        $pizza = Product::create([
            'name' => 'Supreme Cheese Pizza (Large)',
            'code' => 'TEST-FP-PIZZA',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 470.00,
            'sale_price' => 1500.00,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        // Recipe: 1 dough ball + 0.350 kg cheese per pizza
        RecipeItem::create([
            'product_id' => $pizza->id,
            'ingredient_id' => $dough->id,
            'quantity' => 1.0,
            'unit_cost' => 120.00,
            'subtotal' => 120.00,
        ]);

        RecipeItem::create([
            'product_id' => $pizza->id,
            'ingredient_id' => $cheese->id,
            'quantity' => 0.35,
            'unit_cost' => 1000.00,
            'subtotal' => 350.00,
        ]);

        // Create an order for 2x Large Pizzas
        $order = Order::create([
            'order_number' => 'TAK-2026000099',
            'order_type' => 'TAKEAWAY',
            'status' => 'pending',
            'payment_status' => 'paid',
            'subtotal' => 3000.00,
            'grand_total' => 3000.00,
            'paid_amount' => 3000.00,
            'user_id' => $this->user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $pizza->id,
            'product_name' => $pizza->name,
            'quantity' => 2.0, // 2 Pizzas ordered
            'unit_price' => 1500.00,
            'unit_cost' => 470.00,
            'subtotal' => 3000.00,
        ]);

        // Finalize order via OrderService
        app(OrderService::class)->finalizeOrder($order);

        // Expected Deductions:
        // Dough: 10.0 - (2 * 1.0) = 8.0 pcs
        // Cheese: 5.0 - (2 * 0.350) = 5.0 - 0.70 = 4.300 kg
        $dough->refresh();
        $cheese->refresh();

        $this->assertEquals(8.0, (float) $dough->current_stock);
        $this->assertEquals(4.3, (float) $cheese->current_stock);

        // Verify StockMovements
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $dough->id,
            'movement_type' => 'sale',
            'quantity' => -2.0,
            'balance_after' => 8.0,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $cheese->id,
            'movement_type' => 'sale',
            'quantity' => -0.7,
            'balance_after' => 4.3,
        ]);
    }

    public function test_combo_deal_containing_recipe_items_deducts_raw_materials(): void
    {
        app(CashShiftService::class)->openShift(5000.00, 'Test Shift', $this->user->id);

        $dough = Product::create([
            'name' => 'Large Pizza Dough Ball',
            'code' => 'TEST-RM-DOUGH',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 120.00,
            'sale_price' => 0.00,
            'current_stock' => 10.0,
            'is_active' => true,
        ]);

        $pizza = Product::create([
            'name' => 'Supreme Cheese Pizza (Large)',
            'code' => 'TEST-FP-PIZZA',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 120.00,
            'sale_price' => 1500.00,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        RecipeItem::create([
            'product_id' => $pizza->id,
            'ingredient_id' => $dough->id,
            'quantity' => 1.0,
            'unit_cost' => 120.00,
            'subtotal' => 120.00,
        ]);

        // Canned Drink (Retail / Standard)
        $drink = Product::create([
            'name' => 'Cola 1.5L',
            'code' => 'TEST-RT-COLA',
            'sku' => 'TEST-RT-COLA',
            'type' => 'standard',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 150.00,
            'sale_price' => 220.00,
            'current_stock' => 20.0,
            'is_active' => true,
        ]);

        // Create Combo Deal Product
        $dealProduct = Product::create([
            'name' => 'Family Pizza Combo Deal',
            'code' => 'TEST-DEAL-01',
            'sku' => 'TEST-DEAL-01',
            'type' => 'menu_item',
            'is_deal' => true,
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 270.00,
            'sale_price' => 1650.00,
            'current_stock' => 0,
            'is_active' => true,
        ]);

        $deal = Deal::create([
            'name' => 'Family Pizza Combo Deal',
            'code' => 'TEST-DEAL-01',
            'price' => 1650.00,
            'product_id' => $dealProduct->id,
            'is_active' => true,
        ]);

        // 1x Pizza + 1x Cola in the Deal
        $deal->items()->create(['product_id' => $pizza->id, 'quantity' => 1.0]);
        $deal->items()->create(['product_id' => $drink->id, 'quantity' => 1.0]);

        // Create and finalize order for 1x Family Combo Deal
        $order = Order::create([
            'order_number' => 'TAK-2026000100',
            'order_type' => 'TAKEAWAY',
            'status' => 'pending',
            'payment_status' => 'paid',
            'subtotal' => 1650.00,
            'grand_total' => 1650.00,
            'paid_amount' => 1650.00,
            'user_id' => $this->user->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $dealProduct->id,
            'product_name' => $dealProduct->name,
            'quantity' => 1.0,
            'unit_price' => 1650.00,
            'unit_cost' => 270.00,
            'subtotal' => 1650.00,
        ]);

        app(OrderService::class)->finalizeOrder($order);

        $dough->refresh();
        $drink->refresh();

        // Dough (from Pizza recipe) deducted by 1 -> 9.0
        $this->assertEquals(9.0, (float) $dough->current_stock);
        // Cola (standard good) deducted by 1 -> 19.0
        $this->assertEquals(19.0, (float) $drink->current_stock);
    }
}
