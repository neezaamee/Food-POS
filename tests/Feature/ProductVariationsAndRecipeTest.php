<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Category;
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

class ProductVariationsAndRecipeTest extends TestCase
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
            'slug' => 'pizza-specials',
        ], [
            'name' => 'Pizza Specials',
            'is_active' => true,
        ]);

        $this->unitPcs = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'is_active' => true]);
        $this->unitKg = Unit::firstOrCreate(['code' => 'KG'], ['name' => 'Kilograms', 'is_active' => true]);
    }

    public function test_can_create_parent_product_with_size_variants(): void
    {
        $response = $this->post(route('resources.products.store'), [
            'name' => 'Chicken Fajita Pizza',
            'code' => 'PZ-FAJITA',
            'sku' => 'PZ-FAJITA',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 1200.00,
            'cost_price' => 500.00,
            'type' => 'menu_item',
            'is_active' => 1,
            'has_variants' => 1,
            'variants' => [
                [
                    'name' => 'Small',
                    'sale_price' => 700.00,
                    'cost_price' => 250.00,
                    'sku' => 'PZ-FAJITA-S',
                ],
                [
                    'name' => 'Medium',
                    'sale_price' => 1100.00,
                    'cost_price' => 450.00,
                    'sku' => 'PZ-FAJITA-M',
                ],
                [
                    'name' => 'Large',
                    'sale_price' => 1600.00,
                    'cost_price' => 650.00,
                    'sku' => 'PZ-FAJITA-L',
                ],
            ],
        ]);

        $response->assertRedirect();

        $parent = Product::where('code', 'PZ-FAJITA')->first();
        $this->assertNotNull($parent);
        $this->assertTrue($parent->has_variants);
        $this->assertNull($parent->parent_id);

        $variants = $parent->variants()->get();
        $this->assertCount(3, $variants);

        $small = $variants->firstWhere('variation_name', 'Small');
        $this->assertNotNull($small);
        $this->assertEquals('Chicken Fajita Pizza (Small)', $small->name);
        $this->assertEquals(700.00, (float) $small->sale_price);
        $this->assertEquals($parent->id, $small->parent_id);

        $large = $variants->firstWhere('variation_name', 'Large');
        $this->assertNotNull($large);
        $this->assertEquals('Chicken Fajita Pizza (Large)', $large->name);
        $this->assertEquals(1600.00, (float) $large->sale_price);

        $this->assertEquals('Rs. 700 - 1,600', $parent->formattedPriceRange());
    }

    public function test_can_manage_sizes_on_existing_product(): void
    {
        $parent = Product::create([
            'name' => 'Gourmet Burger',
            'code' => 'BGR-01',
            'sku' => 'BGR-01',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 550.00,
            'cost_price' => 220.00,
            'type' => 'menu_item',
            'is_active' => true,
            'has_variants' => false,
        ]);

        $response = $this->post(route('resources.products.variants.save', $parent->id), [
            'has_variants' => 1,
            'variants' => [
                [
                    'name' => 'Single Patty',
                    'sale_price' => 550.00,
                    'cost_price' => 220.00,
                ],
                [
                    'name' => 'Double Patty',
                    'sale_price' => 850.00,
                    'cost_price' => 380.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        $parent->refresh();
        $this->assertTrue($parent->has_variants);
        $this->assertCount(2, $parent->variants);
        $this->assertEquals('Rs. 550 - 850', $parent->formattedPriceRange());
    }

    public function test_each_size_variant_has_independent_recipe_bom(): void
    {
        $doughSmall = Product::create([
            'name' => 'Dough Ball Small',
            'code' => 'RM-DOUGH-S',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 80.00,
            'sale_price' => 0.00,
            'current_stock' => 100,
            'is_active' => true,
        ]);

        $doughLarge = Product::create([
            'name' => 'Dough Ball Large',
            'code' => 'RM-DOUGH-L',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 150.00,
            'sale_price' => 0.00,
            'current_stock' => 100,
            'is_active' => true,
        ]);

        $cheese = Product::create([
            'name' => 'Mozzarella Cheese',
            'code' => 'RM-CHEESE',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitKg->id,
            'cost_price' => 1200.00,
            'sale_price' => 0.00,
            'current_stock' => 50,
            'is_active' => true,
        ]);

        $parent = Product::create([
            'name' => 'BBQ Chicken Pizza',
            'code' => 'PZ-BBQ',
            'sku' => 'PZ-BBQ',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 1000.00,
            'cost_price' => 400.00,
            'has_variants' => true,
            'is_active' => true,
        ]);

        $varSmall = Product::create([
            'name' => 'BBQ Chicken Pizza (Small)',
            'code' => 'PZ-BBQ-SMA',
            'sku' => 'PZ-BBQ-SMA',
            'parent_id' => $parent->id,
            'variation_name' => 'Small',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 650.00,
            'cost_price' => 260.00,
            'is_active' => true,
        ]);

        $varLarge = Product::create([
            'name' => 'BBQ Chicken Pizza (Large)',
            'code' => 'PZ-BBQ-LAR',
            'sku' => 'PZ-BBQ-LAR',
            'parent_id' => $parent->id,
            'variation_name' => 'Large',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 1500.00,
            'cost_price' => 570.00,
            'is_active' => true,
        ]);

        // Save Recipe for Small Variant (1x Small Dough + 0.15kg Cheese)
        // Expected cost: 80 + (0.15 * 1200 = 180) = 260
        $this->postJson(route('resources.products.recipe.save', $parent->id), [
            'target_product_id' => $varSmall->id,
            'ingredients' => [
                ['ingredient_id' => $doughSmall->id, 'quantity' => 1.0, 'unit_cost' => 80.00],
                ['ingredient_id' => $cheese->id, 'quantity' => 0.15, 'unit_cost' => 1200.00],
            ],
        ])->assertJson(['success' => true]);

        // Save Recipe for Large Variant (1x Large Dough + 0.35kg Cheese)
        // Expected cost: 150 + (0.35 * 1200 = 420) = 570
        $this->postJson(route('resources.products.recipe.save', $parent->id), [
            'target_product_id' => $varLarge->id,
            'ingredients' => [
                ['ingredient_id' => $doughLarge->id, 'quantity' => 1.0, 'unit_cost' => 150.00],
                ['ingredient_id' => $cheese->id, 'quantity' => 0.35, 'unit_cost' => 1200.00],
            ],
        ])->assertJson(['success' => true]);

        // Verify Recipe API response
        $recipeJson = $this->getJson(route('resources.products.recipe', $parent->id))->assertOk()->json();
        $this->assertTrue($recipeJson['has_variants']);
        $this->assertCount(2, $recipeJson['variants']);

        $varSmall->refresh();
        $this->assertEquals(260.00, $varSmall->calculateRecipeCost());

        $varLarge->refresh();
        $this->assertEquals(570.00, $varLarge->calculateRecipeCost());
    }

    public function test_livewire_pos_size_selection_flow_and_direct_child_code(): void
    {
        // Open Shift
        app(CashShiftService::class)->openShift(5000, 'Morning Shift');

        $parent = Product::create([
            'name' => 'Pepperoni Passion',
            'code' => 'PZ-PEP',
            'sku' => 'PZ-PEP',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 1100.00,
            'cost_price' => 450.00,
            'has_variants' => true,
            'is_active' => true,
        ]);

        $varSmall = Product::create([
            'name' => 'Pepperoni Passion (Small)',
            'code' => 'PZ-PEP-S',
            'sku' => 'PZ-PEP-S',
            'parent_id' => $parent->id,
            'variation_name' => 'Small',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 750.00,
            'cost_price' => 300.00,
            'is_active' => true,
        ]);

        $varLarge = Product::create([
            'name' => 'Pepperoni Passion (Large)',
            'code' => 'PZ-PEP-L',
            'sku' => 'PZ-PEP-L',
            'parent_id' => $parent->id,
            'variation_name' => 'Large',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 1650.00,
            'cost_price' => 650.00,
            'is_active' => true,
        ]);

        // 1. Clicking parent product card opens variant modal
        Livewire::test(PosScreen::class)
            ->call('addToCart', $parent->id)
            ->assertSet('showVariantModal', true)
            ->assertSet('selectedParentProductId', $parent->id)
            // 2. Select Large size -> adds Large child variant to cart and closes modal
            ->call('selectVariantAndAddToCart', $varLarge->id)
            ->assertSet('showVariantModal', false)
            ->assertCount('cart', 1)
            ->assertSee('Pepperoni Passion (Large)')
            ->assertSee('1,650');

        // 3. Direct barcode punch on child code adds child directly without popup
        Livewire::test(PosScreen::class)
            ->set('productCodeInput', 'PZ-PEP-S')
            ->call('handleProductCodeEnter')
            ->assertSet('showVariantModal', false)
            ->assertCount('cart', 1)
            ->assertSee('Pepperoni Passion (Small)')
            ->assertSee('750');

        // 4. Typing parent code in input triggers size modal
        Livewire::test(PosScreen::class)
            ->set('productCodeInput', 'PZ-PEP')
            ->call('handleProductCodeEnter')
            ->assertSet('showVariantModal', true)
            ->assertSet('selectedParentProductId', $parent->id)
            // 5. ESC key closes the size modal
            ->call('closeAnyOpenModal')
            ->assertSet('showVariantModal', false);
    }

    public function test_finalizing_order_deducts_specific_size_raw_materials(): void
    {
        // 1. Setup raw materials with initial stock
        $doughSmall = Product::create([
            'name' => 'Dough Small',
            'code' => 'DOUGH-S',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 70.00,
            'sale_price' => 0.00,
            'current_stock' => 20,
            'is_active' => true,
        ]);

        $doughLarge = Product::create([
            'name' => 'Dough Large',
            'code' => 'DOUGH-L',
            'type' => 'raw_material',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'cost_price' => 140.00,
            'sale_price' => 0.00,
            'current_stock' => 20,
            'is_active' => true,
        ]);

        $parent = Product::create([
            'name' => 'Tikka Feast Pizza',
            'code' => 'PZ-TIKKA',
            'sku' => 'PZ-TIKKA',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 1200.00,
            'cost_price' => 500.00,
            'has_variants' => true,
            'is_active' => true,
        ]);

        $varLarge = Product::create([
            'name' => 'Tikka Feast Pizza (Large)',
            'code' => 'PZ-TIKKA-L',
            'sku' => 'PZ-TIKKA-L',
            'parent_id' => $parent->id,
            'variation_name' => 'Large',
            'type' => 'menu_item',
            'category_id' => $this->category->id,
            'unit_id' => $this->unitPcs->id,
            'sale_price' => 1500.00,
            'cost_price' => 550.00,
            'is_active' => true,
        ]);

        // Recipe: 1x Large Dough for Large Pizza
        RecipeItem::create([
            'product_id' => $varLarge->id,
            'ingredient_id' => $doughLarge->id,
            'quantity' => 1.0,
            'unit_cost' => 140.00,
        ]);

        // Create Order for 2x Large Pizza
        $order = Order::create([
            'order_number' => 'ORD-TEST-VAR',
            'order_type' => 'TAKEAWAY',
            'order_status' => 'draft',
            'payment_status' => 'unpaid',
            'subtotal' => 3000.00,
            'grand_total' => 3000.00,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $varLarge->id,
            'product_name' => $varLarge->name,
            'quantity' => 2,
            'unit_price' => 1500.00,
            'unit_cost' => 550.00,
            'subtotal' => 3000.00,
        ]);

        // Finalize order
        app(OrderService::class)->finalizeOrder($order);

        // Verify stock deduction:
        // Large dough should be deducted by 2 (20 - 2 = 18)
        $doughLarge->refresh();
        $this->assertEquals(18, (float) $doughLarge->current_stock);

        // Small dough must remain untouched (20)
        $doughSmall->refresh();
        $this->assertEquals(20, (float) $doughSmall->current_stock);

        // Stock movement recorded
        $movement = StockMovement::where('product_id', $doughLarge->id)
            ->where('movement_type', 'sale')
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(-2.0, (float) $movement->quantity);
    }
}
