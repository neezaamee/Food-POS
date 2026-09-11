<?php

namespace App\Services\SaaS;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;

class SubscriptionService
{
    /**
     * Get active subscription for tenant
     */
    public function getCurrentSubscription(?Tenant $tenant = null): ?Subscription
    {
        $tenant = $tenant ?? TenantContext::current();

        if (! $tenant) {
            return null;
        }

        return $tenant->activeSubscription ?? $tenant->subscriptions()->latest()->first();
    }

    /**
     * Get plan for tenant
     */
    public function getCurrentPlan(?Tenant $tenant = null): ?Plan
    {
        return $this->getCurrentSubscription($tenant)?->plan;
    }

    /**
     * Check if tenant is permitted to create another product
     */
    public function canCreateProduct(?Tenant $tenant = null): bool
    {
        $plan = $this->getCurrentPlan($tenant);

        if (! $plan || $plan->max_products === null || (int) $plan->max_products <= 0) {
            return true;
        }

        return Product::count() < $plan->max_products;
    }

    /**
     * Check if tenant is permitted to invite/create another staff user
     */
    public function canCreateUser(?Tenant $tenant = null): bool
    {
        $plan = $this->getCurrentPlan($tenant);

        if (! $plan || $plan->max_users === null || (int) $plan->max_users <= 0) {
            return true;
        }

        return User::count() < $plan->max_users;
    }

    /**
     * Check if tenant can process another order this month
     */
    public function canCreateOrder(?Tenant $tenant = null): bool
    {
        $plan = $this->getCurrentPlan($tenant);

        if (! $plan || $plan->max_orders_per_month === null || (int) $plan->max_orders_per_month <= 0) {
            return true;
        }

        $monthlyOrders = Order::whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->count();

        return $monthlyOrders < $plan->max_orders_per_month;
    }

    /**
     * Check if tenant can add another restaurant table
     */
    public function canCreateTable(?Tenant $tenant = null): bool
    {
        $plan = $this->getCurrentPlan($tenant);

        if (! $plan || $plan->max_tables === null || (int) $plan->max_tables <= 0) {
            return true;
        }

        return RestaurantTable::count() < $plan->max_tables;
    }

    /**
     * Check if feature module is enabled for tenant's plan
     */
    public function hasFeature(string $feature, ?Tenant $tenant = null): bool
    {
        $plan = $this->getCurrentPlan($tenant);

        if (! $plan) {
            return true;
        }

        $features = $plan->features ?? [];

        return in_array($feature, $features, true);
    }

    /**
     * Compile comprehensive plan usage statistics
     */
    public function getUsageStats(?Tenant $tenant = null): array
    {
        $plan = $this->getCurrentPlan($tenant);

        $productsCount = Product::count();
        $usersCount = User::count();
        $monthlyOrders = Order::whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ])->count();
        $tablesCount = RestaurantTable::count();

        return [
            'plan_name' => $plan?->name ?? 'None',
            'products' => [
                'current' => $productsCount,
                'limit' => $plan?->max_products,
                'remaining' => $plan?->max_products ? max(0, $plan->max_products - $productsCount) : 'Unlimited',
            ],
            'users' => [
                'current' => $usersCount,
                'limit' => $plan?->max_users,
                'remaining' => $plan?->max_users ? max(0, $plan->max_users - $usersCount) : 'Unlimited',
            ],
            'orders' => [
                'current' => $monthlyOrders,
                'limit' => $plan?->max_orders_per_month,
                'remaining' => $plan?->max_orders_per_month ? max(0, $plan->max_orders_per_month - $monthlyOrders) : 'Unlimited',
            ],
            'tables' => [
                'current' => $tablesCount,
                'limit' => $plan?->max_tables,
                'remaining' => $plan?->max_tables ? max(0, $plan->max_tables - $tablesCount) : 'Unlimited',
            ],
        ];
    }
}
