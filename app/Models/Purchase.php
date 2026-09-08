<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_number',
        'supplier_name',
        'invoice_number',
        'purchase_date',
        'subtotal',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'payment_method',
        'status',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categoriesList(): string
    {
        return $this->items
            ->map(fn ($item) => $item->product?->category?->name)
            ->filter()
            ->unique()
            ->join(', ');
    }

    public function totalQuantity(): float
    {
        return (float) $this->items->sum('quantity');
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->grand_total;
    }
}
