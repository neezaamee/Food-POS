<?php

namespace App\Models;

use App\Services\SaaS\FeatureAccessService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'legal_name',
        'phone',
        'email',
        'address',
        'city',
        'province',
        'country',
        'ntn',
        'strn',
        'currency',
        'timezone',
        'status',
        'trial_ends_at',
        'disabled_at',
        'is_setup_completed',
        'logo',
        'metadata',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'disabled_at' => 'datetime',
        'is_setup_completed' => 'boolean',
        'metadata' => 'array',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owners()
    {
        return $this->belongsToMany(User::class, 'tenant_owners')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryOwner(): ?User
    {
        return $this->owners()->wherePivot('is_primary', true)->first()
            ?? $this->owners()->first()
            ?? $this->users()->whereIn('role', ['owner', 'admin'])->first();
    }

    public function featureOverrides(): HasMany
    {
        return $this->hasMany(TenantFeatureOverride::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /**
     * Get currently active or latest subscription
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['active', 'trial'])
            ->latestOfMany();
    }

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }

    public function isDisabled(): bool
    {
        return $this->status === 'disabled' || $this->disabled_at !== null;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isActive(): bool
    {
        if ($this->isDisabled() || $this->isSuspended()) {
            return false;
        }

        return $this->status === 'active' || $this->isTrial();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && ($this->trial_ends_at === null || $this->trial_ends_at->isFuture());
    }

    /**
     * Check if tenant plan allows a specific feature (overrides + plan features)
     */
    public function canAccessFeature(string $feature): bool
    {
        return app(FeatureAccessService::class)->allows($this, $feature);
    }

    /**
     * Products count relationship for limits checking
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }
}
