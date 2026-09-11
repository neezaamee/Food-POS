<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\RecipeItem;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Services\SaaS\SubscriptionService;
use App\Services\SaaS\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::parentsOnly()
            ->with(['category', 'brand', 'unit', 'recipeItems.ingredient.unit', 'variants.recipeItems.ingredient.unit', 'variants.unit'])
            ->latest();

        if ($request->filled('type') && in_array($request->type, ['menu_item', 'raw_material', 'standard'])) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('barcode', 'like', $term)
                    ->orWhereHas('variants', function ($vq) use ($term) {
                        $vq->where('name', 'like', $term)
                            ->orWhere('code', 'like', $term);
                    });
            });
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();
        $units = Unit::all();
        $rawMaterials = Product::where('type', 'raw_material')->where('is_active', true)->with('unit')->orderBy('name')->get();

        return view('resources.products.index', compact('products', 'categories', 'brands', 'units', 'rawMaterials'));
    }

    public function store(Request $request)
    {
        $hasVariants = $request->boolean('has_variants');
        $tenantId = TenantContext::id() ?? 1;

        $request->validate([
            'name' => 'required|string|max:150',
            'name_ur' => 'nullable|string|max:150',
            'code' => "required|string|max:50|unique:products,code,NULL,id,tenant_id,{$tenantId}",
            'category_id' => 'required|exists:categories,id',
            'type' => 'nullable|in:menu_item,raw_material,standard',
            'sale_price' => $hasVariants ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'opening_stock' => 'nullable|numeric|min:0',
            'has_variants' => 'nullable|boolean',
            'variants' => $hasVariants ? 'required|array|min:1' : 'nullable|array',
            'variants.*.name' => 'required_if:has_variants,1|string|max:50',
            'variants.*.sale_price' => 'required_if:has_variants,1|numeric|min:0',
        ]);

        if (! app(SubscriptionService::class)->canCreateProduct()) {
            return back()->with('error', 'You have reached the maximum product limit allowed by your subscription plan. Please upgrade to add more products.');
        }

        return DB::transaction(function () use ($request, $hasVariants) {
            $parent = Product::create([
                'name' => $request->name,
                'name_ur' => $request->name_ur,
                'type' => $request->type ?: 'menu_item',
                'code' => $request->code,
                'sku' => $request->sku ?: $request->code,
                'barcode' => $request->barcode ?: rand(100000, 999999),
                'category_id' => $request->category_id,
                'brand_id' => $request->brand_id,
                'unit_id' => $request->unit_id,
                'cost_price' => $request->cost_price ?: 0.00,
                'sale_price' => $request->sale_price ?: 0.00,
                'current_stock' => $request->opening_stock ?: 0,
                'min_stock' => $request->min_stock ?: 5,
                'prep_time_minutes' => $request->prep_time_minutes ?: 15,
                'has_variants' => $hasVariants,
                'is_active' => true,
            ]);

            if ($hasVariants && is_array($request->variants)) {
                $startingPrice = null;
                foreach ($request->variants as $index => $v) {
                    if (empty($v['name'])) {
                        continue;
                    }

                    $varName = trim($v['name']);
                    $varPrice = (float) ($v['sale_price'] ?? 0);
                    $varCost = (float) ($v['cost_price'] ?? 0);
                    $varCode = ! empty($v['code']) ? trim($v['code']) : sprintf('%s-%s', $parent->code, strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $varName), 0, 3) ?: ($index + 1)));

                    // Ensure unique code
                    $candidateCode = $varCode;
                    $counter = 1;
                    while (Product::where('code', $candidateCode)->exists()) {
                        $candidateCode = "{$varCode}-{$counter}";
                        $counter++;
                    }

                    if ($startingPrice === null || $varPrice < $startingPrice) {
                        $startingPrice = $varPrice;
                    }

                    Product::create([
                        'parent_id' => $parent->id,
                        'variation_name' => $varName,
                        'name' => "{$parent->name} ({$varName})",
                        'type' => $parent->type,
                        'code' => $candidateCode,
                        'sku' => $candidateCode,
                        'barcode' => rand(100000, 999999),
                        'category_id' => $parent->category_id,
                        'brand_id' => $parent->brand_id,
                        'unit_id' => $parent->unit_id,
                        'cost_price' => $varCost,
                        'sale_price' => $varPrice,
                        'current_stock' => 0,
                        'min_stock' => $parent->min_stock,
                        'prep_time_minutes' => $parent->prep_time_minutes,
                        'is_active' => true,
                    ]);
                }

                if ($startingPrice !== null) {
                    $parent->sale_price = $startingPrice;
                    $parent->save();
                }
            } elseif ($request->opening_stock > 0) {
                StockMovement::create([
                    'product_id' => $parent->id,
                    'movement_type' => 'opening',
                    'quantity' => $request->opening_stock,
                    'unit_cost' => $request->cost_price,
                    'balance_after' => $request->opening_stock,
                    'notes' => 'Opening Stock',
                    'user_id' => auth()->id(),
                ]);
            }

            return back()->with('success', "Product '{$parent->name}' added successfully!");
        });
    }

    public function update(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:150',
            'name_ur' => 'nullable|string|max:150',
            'type' => 'nullable|in:menu_item,raw_material,standard',
            'sale_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
        ]);

        $product->update($request->only('name', 'name_ur', 'type', 'sale_price', 'cost_price', 'category_id', 'min_stock', 'prep_time_minutes'));

        return back()->with('success', "Product '{$product->name}' updated successfully!");
    }

    public function manageVariants(Request $request, int $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|string|max:50',
            'variants.*.sale_price' => 'required|numeric|min:0',
            'variants.*.code' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($product, $request) {
            $product->has_variants = true;
            $product->save();

            $keptIds = [];
            $lowestPrice = null;

            foreach ($request->variants as $index => $v) {
                $varId = $v['id'] ?? null;
                $varName = trim($v['name']);
                $varPrice = (float) $v['sale_price'];
                $varCode = ! empty($v['code']) ? trim($v['code']) : sprintf('%s-%s', $product->code, strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $varName), 0, 3) ?: ($index + 1)));
                $fullName = "{$product->name} ({$varName})";

                if ($lowestPrice === null || $varPrice < $lowestPrice) {
                    $lowestPrice = $varPrice;
                }

                if ($varId && $existing = Product::where('parent_id', $product->id)->find($varId)) {
                    $existing->update([
                        'variation_name' => $varName,
                        'name' => $fullName,
                        'sale_price' => $varPrice,
                        'code' => $varCode,
                    ]);
                    $keptIds[] = $existing->id;
                } else {
                    $candidateCode = $varCode;
                    $counter = 1;
                    while (Product::where('code', $candidateCode)->exists()) {
                        $candidateCode = "{$varCode}-{$counter}";
                        $counter++;
                    }

                    $newVar = Product::create([
                        'parent_id' => $product->id,
                        'variation_name' => $varName,
                        'name' => $fullName,
                        'type' => $product->type,
                        'code' => $candidateCode,
                        'sku' => $candidateCode,
                        'barcode' => rand(100000, 999999),
                        'category_id' => $product->category_id,
                        'brand_id' => $product->brand_id,
                        'unit_id' => $product->unit_id,
                        'cost_price' => 0.00,
                        'sale_price' => $varPrice,
                        'current_stock' => 0,
                        'min_stock' => $product->min_stock,
                        'prep_time_minutes' => $product->prep_time_minutes,
                        'is_active' => true,
                    ]);
                    $keptIds[] = $newVar->id;
                }
            }

            // Remove deleted variants
            Product::where('parent_id', $product->id)->whereNotIn('id', $keptIds)->delete();

            if ($lowestPrice !== null) {
                $product->sale_price = $lowestPrice;
                $product->save();
            }
        });

        return back()->with('success', "Sizes & Variations for '{$product->name}' updated successfully!");
    }

    public function getRecipe(int $id)
    {
        $product = Product::with([
            'recipeItems.ingredient.unit',
            'variants.recipeItems.ingredient.unit',
        ])->findOrFail($id);

        $mapItems = fn ($items) => $items->map(fn ($item) => [
            'id' => $item->id,
            'ingredient_id' => $item->ingredient_id,
            'name' => $item->ingredient?->name ?? 'Unknown',
            'unit' => $item->ingredient?->unit?->code ?? $item->ingredient?->unit?->short_name ?? 'Units',
            'quantity' => (float) $item->quantity,
            'unit_cost' => (float) $item->unit_cost,
            'subtotal' => (float) $item->subtotal,
            'notes' => $item->notes ?? '',
        ]);

        $variantsData = $product->variants->map(fn ($v) => [
            'id' => $v->id,
            'variation_name' => $v->variation_name ?: $v->name,
            'name' => $v->name,
            'code' => $v->code,
            'sale_price' => (float) $v->sale_price,
            'cost_price' => (float) $v->cost_price,
            'recipe_items' => $mapItems($v->recipeItems),
            'recipe_cost' => $v->calculateRecipeCost(),
            'profit_margin' => $v->calculateProfitMargin(),
        ]);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'sale_price' => (float) $product->sale_price,
                'cost_price' => (float) $product->cost_price,
                'has_variants' => $product->hasVariants(),
            ],
            'has_variants' => $product->hasVariants(),
            'variants' => $variantsData,
            'recipe_items' => $mapItems($product->recipeItems),
            'recipe_cost' => $product->calculateRecipeCost(),
            'profit_margin' => $product->calculateProfitMargin(),
        ]);
    }

    public function getEditData(int $id)
    {
        $product = Product::with(['variants', 'category', 'brand', 'unit'])->findOrFail($id);

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'name_ur' => $product->name_ur,
            'type' => $product->type,
            'sale_price' => (float) $product->sale_price,
            'cost_price' => (float) $product->cost_price,
            'category_id' => $product->category_id,
            'min_stock' => (float) $product->min_stock,
            'prep_time_minutes' => (int) $product->prep_time_minutes,
            'has_variants' => $product->hasVariants(),
            'variants' => $product->variants->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->variation_name ?: $v->name,
                'code' => $v->code,
                'sale_price' => (float) $v->sale_price,
                'cost_price' => (float) $v->cost_price,
            ]),
        ]);
    }

    public function saveRecipe(Request $request, int $id)
    {
        // Support saving recipe for a child variant or the item itself
        $targetId = $request->input('target_product_id', $id);
        $product = Product::findOrFail($targetId);

        $request->validate([
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:products,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.0001',
            'ingredients.*.notes' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($product, $request) {
            $product->recipeItems()->delete();

            if ($request->filled('ingredients') && is_array($request->ingredients)) {
                foreach ($request->ingredients as $item) {
                    $ingredient = Product::find($item['ingredient_id']);
                    if (! $ingredient) {
                        continue;
                    }

                    $qty = (float) $item['quantity'];
                    $cost = (float) $ingredient->cost_price;

                    RecipeItem::create([
                        'product_id' => $product->id,
                        'ingredient_id' => $ingredient->id,
                        'quantity' => $qty,
                        'unit_cost' => $cost,
                        'subtotal' => $qty * $cost,
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Recalculate and update cost_price on the product
            $recipeCost = $product->calculateRecipeCost();
            if ($recipeCost > 0 || $product->recipeItems()->count() > 0) {
                $product->cost_price = $recipeCost;
                $product->save();
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Recipe for '{$product->name}' updated!",
                'cost_price' => (float) $product->cost_price,
            ]);
        }

        return back()->with('success', "Recipe for '{$product->name}' updated! Food Cost: Rs. ".number_format($product->cost_price, 2));
    }

    public function destroy(int $id)
    {
        $product = Product::findOrFail($id);

        DB::transaction(function () use ($product) {
            $product->variants()->delete();
            $product->recipeItems()->delete();
            $product->delete();
        });

        return back()->with('success', "Product '{$product->name}' deleted successfully!");
    }
}
