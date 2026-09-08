<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number',
        'order_type',
        'order_status',
        'kot_status',
        'payment_status',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'table_id',
        'table_name',
        'delivery_area_id',
        'delivery_rider_id',
        'delivery_charge',
        'delivery_distance_km',
        'rider_starting_km',
        'rider_ending_km',
        'rider_total_km',
        'subtotal',
        'discount_type',
        'discount_rate',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'balance_amount',
        'notes',
        'user_id',
        'cash_shift_id',
        'finalized_at',
        'fbr_invoice_number',
        'client_uuid',
        'offline_order_number',
        'is_offline',
        'synced_at',
    ];

    protected $casts = [
        'delivery_charge' => 'decimal:2',
        'delivery_distance_km' => 'decimal:2',
        'rider_starting_km' => 'decimal:2',
        'rider_ending_km' => 'decimal:2',
        'rider_total_km' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'is_offline' => 'boolean',
        'finalized_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function kots()
    {
        return $this->hasMany(Kot::class);
    }

    public function latestKot(): ?Kot
    {
        return $this->kots()->orderByDesc('kot_number')->first();
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function table()
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function deliveryArea()
    {
        return $this->belongsTo(DeliveryArea::class, 'delivery_area_id');
    }

    public function rider()
    {
        return $this->belongsTo(DeliveryRider::class, 'delivery_rider_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cashShift()
    {
        return $this->belongsTo(CashShift::class, 'cash_shift_id');
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function fbrSubmissions()
    {
        return $this->hasMany(FbrSubmission::class);
    }

    public function isDraft(): bool
    {
        return $this->order_status === 'draft';
    }

    public function isFinalized(): bool
    {
        return ! is_null($this->finalized_at);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }
}
