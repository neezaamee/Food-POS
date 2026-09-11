<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'name_ur',
        'code',
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'unit_id',
        'cost_price',
        'sale_price',
        'tax_percent',
        'image',
        'current_stock',
        'min_stock',
        'prep_time_minutes',
        'is_active',
        'type', // menu_item, raw_material, standard
        'parent_id',
        'variation_name',
        'has_variants',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'prep_time_minutes' => 'integer',
        'is_active' => 'boolean',
        'has_variants' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function variants()
    {
        return $this->hasMany(Product::class, 'parent_id')->orderBy('sale_price');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    public function deal()
    {
        return $this->hasOne(Deal::class);
    }

    /**
     * Ingredients consumed by this finished menu item
     */
    public function recipeItems()
    {
        return $this->hasMany(RecipeItem::class, 'product_id');
    }

    /**
     * Finished recipes where this raw material is used
     */
    public function usedInRecipes()
    {
        return $this->hasMany(RecipeItem::class, 'ingredient_id');
    }

    public function isRawMaterial(): bool
    {
        return $this->type === 'raw_material';
    }

    public function isMenuItem(): bool
    {
        return $this->type === 'menu_item';
    }

    public function isStandard(): bool
    {
        return $this->type === 'standard';
    }

    public function hasRecipe(): bool
    {
        return $this->recipeItems()->exists();
    }

    /**
     * Calculate live recipe cost from constituent ingredient costs
     */
    public function calculateRecipeCost(): float
    {
        if ($this->recipeItems->isEmpty()) {
            return (float) $this->cost_price;
        }

        return (float) $this->recipeItems->sum(function ($item) {
            $cost = $item->ingredient ? (float) $item->ingredient->cost_price : (float) $item->unit_cost;

            return (float) $item->quantity * $cost;
        });
    }

    /**
     * Calculate live gross profit margin percentage
     */
    public function calculateProfitMargin(): float
    {
        $sale = (float) $this->sale_price;
        if ($sale <= 0) {
            return 0.00;
        }

        $cost = $this->hasRecipe() ? $this->calculateRecipeCost() : (float) $this->cost_price;

        return round((($sale - $cost) / $sale) * 100, 1);
    }

    public function scopeParentsOnly($query)
    {
        return $query->whereNull('parent_id');
    }

    public function isVariant(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function hasVariants(): bool
    {
        return (bool) $this->has_variants || $this->variants()->exists();
    }

    public function formattedPriceRange(): string
    {
        if (! $this->hasVariants()) {
            return 'Rs. '.number_format($this->sale_price, 2);
        }

        $this->loadMissing('variants');
        $prices = $this->variants->pluck('sale_price')->filter()->all();
        if (empty($prices)) {
            return 'Rs. '.number_format($this->sale_price, 2);
        }

        $min = min($prices);
        $max = max($prices);

        if ($min == $max) {
            return 'Rs. '.number_format($min, 2);
        }

        return 'Rs. '.number_format($min).' - '.number_format($max);
    }
}
