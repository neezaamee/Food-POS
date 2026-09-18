<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Cash\CashShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class PosLiveImprovementsAndProductImageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::first() ?? User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);
    }

    public function test_product_image_can_be_uploaded_viewed_and_removed(): void
    {
        $category = Category::create(['name' => 'Burgers', 'slug' => 'burgers', 'is_active' => true]);

        $imageFile = UploadedFile::fake()->image('beef_burger.jpg', 400, 400);

        // 1. Store product with image upload
        $response = $this->post(route('resources.products.store'), [
            'name' => 'Monster Beef Burger',
            'name_ur' => 'مانسٹر بیف برگر',
            'code' => 'BUR-MST-01',
            'category_id' => $category->id,
            'sale_price' => 850.00,
            'cost_price' => 450.00,
            'image' => $imageFile,
        ]);

        $response->assertRedirect();
        $product = Product::where('code', 'BUR-MST-01')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->image);
        $this->assertStringContainsString('uploads/products/', $product->image);
        $this->assertNotNull($product->image_url);
        $this->assertStringContainsString('uploads/products/', $product->image_url);

        $savedImagePath = public_path($product->image);
        $this->assertFileExists($savedImagePath);

        // 2. Catalog page displays product image thumbnail
        $catalogResponse = $this->get(route('resources.products.index'));
        $catalogResponse->assertOk();
        $catalogResponse->assertSee($product->image_url);

        // 3. Update product with a new image
        $newImageFile = UploadedFile::fake()->image('updated_burger.png', 400, 400);
        $updateResponse = $this->put(route('resources.products.update', $product->id), [
            'name' => 'Monster Beef Burger Deluxe',
            'type' => 'menu_item',
            'category_id' => $category->id,
            'sale_price' => 950.00,
            'cost_price' => 500.00,
            'image' => $newImageFile,
        ]);

        $updateResponse->assertRedirect();
        $product->refresh();
        $this->assertEquals('Monster Beef Burger Deluxe', $product->name);
        $this->assertFileExists(public_path($product->image));

        // Clean up old file was replaced
        if (file_exists($savedImagePath) && $savedImagePath !== public_path($product->image)) {
            @unlink($savedImagePath);
        }

        // 4. Remove image option
        $removeResponse = $this->put(route('resources.products.update', $product->id), [
            'name' => 'Monster Beef Burger Deluxe',
            'type' => 'menu_item',
            'category_id' => $category->id,
            'sale_price' => 950.00,
            'cost_price' => 500.00,
            'remove_image' => '1',
        ]);

        $removeResponse->assertRedirect();
        $product->refresh();
        $this->assertNull($product->image);
        $this->assertNull($product->image_url);
    }

    public function test_pos_live_supports_10_dishes_with_dense_compact_rows_and_images(): void
    {
        // Open shift for cashier
        app(CashShiftService::class)->openShift(
            openingCash: 5000,
            notes: 'Test shift',
            userId: $this->user->id
        );

        $category = Category::create(['name' => 'Fast Food', 'slug' => 'fast-food', 'is_active' => true]);

        // Create 10 distinct products
        $products = [];
        for ($i = 1; $i <= 10; $i++) {
            $products[] = Product::create([
                'name' => "Special Dish #{$i}",
                'code' => "DISH-0{$i}",
                'category_id' => $category->id,
                'sale_price' => 100 * $i,
                'cost_price' => 50 * $i,
                'image' => "uploads/products/dish_{$i}.jpg",
                'is_active' => true,
            ]);
        }

        $posTest = Livewire::test(PosScreen::class)
            ->set('customerName', 'Ali Raza')
            ->set('customerPhone', '03001234567');

        // Add all 10 dishes to the cart
        foreach ($products as $prod) {
            $posTest->call('addToCart', $prod->id);
        }

        // Verify cart has all 10 items
        $posTest->assertCount('cart', 10);

        // Verify compact cart row classes and scroll container render in HTML
        $posTest->assertSee('pos-cart-item');
        $posTest->assertSee('posCartScrollContainer');
        $posTest->assertSee('pos-customer-strip');
        $posTest->assertSee('pos-summary-box');

        // Verify all 10 dishes are rendered
        for ($i = 1; $i <= 10; $i++) {
            $posTest->assertSee("Special Dish #{$i}");
        }

        // Punch order
        $posTest->call('saveOpenOrder');
        $order = Order::latest('id')->first();
        $this->assertNotNull($order);
        $this->assertCount(10, $order->items);
    }

    public function test_customer_thermal_bill_matches_kot_bold_format(): void
    {
        $category = Category::create(['name' => 'BBQ', 'slug' => 'bbq', 'is_active' => true]);

        $product = Product::create([
            'name' => 'Chicken Malai Boti',
            'name_ur' => 'چکن ملائی بوٹی',
            'code' => 'BBQ-MALAI',
            'category_id' => $category->id,
            'sale_price' => 750.00,
            'cost_price' => 350.00,
            'is_active' => true,
        ]);

        app(CashShiftService::class)->openShift(
            openingCash: 5000,
            notes: 'Test shift',
            userId: $this->user->id
        );

        Livewire::test(PosScreen::class)
            ->set('customerName', 'Hassan Tariq')
            ->set('customerPhone', '03123456789')
            ->call('addToCart', $product->id)
            ->call('saveOpenOrder');

        $order = Order::latest('id')->first();
        $this->assertNotNull($order);

        $response = $this->get(route('orders.thermal', $order->id));
        $response->assertOk();

        // 1. KOT font family support
        $response->assertSee('Noto Nastaliq Urdu');
        $response->assertSee('Jameel Noori Nastaleeq');

        // 2. Urdu product name rendered in bill
        $response->assertSee('چکن ملائی بوٹی');
        $response->assertSee('Chicken Malai Boti');

        // 3. High-contrast bold styling matching KOT format
        $response->assertSee('TOTAL:');
        $response->assertSee('badge-receipt');
        $response->assertSee($order->order_number);
    }
}
