<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'sale_price',
        'cost_price',
        'image',
        'is_active',
        'product_id',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(DealItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate regular retail value of included items
     */
    public function originalTotalValue(): float
    {
        return (float) $this->items->sum(function ($item) {
            $price = $item->product ? (float) $item->product->sale_price : (float) $item->unit_price;

            return $price * (float) $item->quantity;
        });
    }

    /**
     * Calculate cost of components
     */
    public function calculatedCost(): float
    {
        return (float) $this->items->sum(function ($item) {
            $cost = $item->product ? (float) $item->product->cost_price : 0.00;

            return $cost * (float) $item->quantity;
        });
    }

    /**
     * Calculate customer savings vs buying items separately
     */
    public function savingsAmount(): float
    {
        $orig = $this->originalTotalValue();
        $sale = (float) $this->sale_price;

        return max(0.00, $orig - $sale);
    }

    /**
     * Sync this deal as a purchasable Product in POS under "Packages & Deals" category
     */
    public function syncProduct(): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'packages-deals'],
            [
                'name' => 'Packages & Deals',
                'description' => 'Discounted meal combos and bundled item packages',
                'is_active' => true,
            ]
        );

        $product = $this->product_id ? Product::find($this->product_id) : null;

        if (! $product) {
            $product = new Product;
            $product->code = $this->code;
            $product->sku = $this->code;
            $product->barcode = (string) rand(100000, 999999);
        }

        $product->name = $this->name;
        $product->category_id = $category->id;
        $product->sale_price = $this->sale_price;
        $product->cost_price = $this->cost_price ?: $this->calculatedCost();
        $product->is_active = $this->is_active;
        $product->image = $this->image;
        $product->save();

        if ($this->product_id !== $product->id) {
            $this->product_id = $product->id;
            $this->saveQuietly();
        }

        return $product;
    }
}
