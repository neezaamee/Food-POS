<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_yearly',
        'is_active',
        'is_popular',
        'max_users',
        'max_products',
        'max_monthly_orders',
        'max_tables',
        'features',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'max_users' => 'integer',
        'max_products' => 'integer',
        'max_monthly_orders' => 'integer',
        'max_tables' => 'integer',
        'features' => 'array',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];

        return in_array('*', $features, true) || in_array($feature, $features, true);
    }

    public function getPriceAttribute(): float
    {
        return (float) ($this->price_monthly ?? 0);
    }

    public function setPriceAttribute($value): void
    {
        $this->attributes['price_monthly'] = $value;
    }

    public function getBillingCycleAttribute(): string
    {
        return 'monthly';
    }

    public function getMaxOrdersPerMonthAttribute(): ?int
    {
        return $this->max_monthly_orders ?: null;
    }

    public function setMaxOrdersPerMonthAttribute($value): void
    {
        $this->attributes['max_monthly_orders'] = $value ?: 0;
    }
}
