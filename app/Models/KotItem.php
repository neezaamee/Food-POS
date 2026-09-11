<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KotItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'kot_id',
        'order_item_id',
        'product_id',
        'product_name',
        'product_name_ur',
        'quantity',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function kot()
    {
        return $this->belongsTo(Kot::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
