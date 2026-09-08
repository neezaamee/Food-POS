<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'delivery_charge',
        'estimated_distance_km',
        'is_active',
    ];

    protected $casts = [
        'delivery_charge' => 'decimal:2',
        'estimated_distance_km' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
