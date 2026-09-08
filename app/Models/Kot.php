<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kot extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'kot_number',
        'status',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'kot_number' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(KotItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
