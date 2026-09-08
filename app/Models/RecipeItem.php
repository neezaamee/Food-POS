<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'ingredient_id',
        'quantity',
        'unit_cost',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /**
     * The finished menu item this recipe line belongs to
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * The raw material / ingredient being consumed
     */
    public function ingredient()
    {
        return $this->belongsTo(Product::class, 'ingredient_id');
    }

    /**
     * Recalculate and update subtotal based on current ingredient cost
     */
    public function recalculateSubtotal(): float
    {
        $cost = $this->ingredient ? (float) $this->ingredient->cost_price : (float) $this->unit_cost;
        $this->unit_cost = $cost;
        $this->subtotal = (float) $this->quantity * $cost;
        $this->save();

        return (float) $this->subtotal;
    }
}
