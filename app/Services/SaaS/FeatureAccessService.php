<?php

namespace App\Services\SaaS;

use App\Models\Order;
use App\Models\PlanFeature;
use App\Models\RestaurantTable;
use App\Models\Tenant;
use App\Models\TenantFeatureOverride;

class FeatureAccessService
{
    /**
     * Check if a tenant has access to a specific feature.
     * Evaluates Tenant Overrides first, then Plan features.
     */
    public function allows(Tenant|int|null $tenant, string $featureCode): bool
    {
        if (! $tenant) {
            return false;
        }

        if (is_numeric($tenant)) {
            $tenant = Tenant::find($tenant);
            if (! $tenant) {
                return false;
            }
        }

        // Suspended or disabled tenants have all feature access revoked
        if ($tenant->isSuspended() || $tenant->isDisabled()) {
            return false;
        }

        // 1. Check explicit per-tenant override
        $override = TenantFeatureOverride::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($featureCode) {
                $q->where('feature_key', $featureCode);
            })
            ->first();

        if ($override !== null) {
            return (bool) $override->is_enabled;
        }

        // 2. Default tenant #1 retains legacy full access unless explicitly overridden
        if ($tenant->id === 1) {
            return true;
        }

        // 3. Fallback to active subscription plan
        $plan = $tenant->currentPlan();
        if (! $plan) {
            return false;
        }

        return $plan->hasFeature($featureCode);
    }

    /**
     * Check if tenant is within quota for a given resource.
     * Supported resources: 'users', 'products', 'monthly_orders', 'tables'.
     */
    public function hasQuota(Tenant|int $tenant, string $resource): bool
    {
        if (is_numeric($tenant)) {
            $tenant = Tenant::findOrFail($tenant);
        }

        if ($tenant->id === 1) {
            return true; // Default tenant has unlimited quota
        }

        $plan = $tenant->currentPlan();
        if (! $plan) {
            return false;
        }

        return match ($resource) {
            'users' => $plan->max_users === 0 || $tenant->users()->count() < $plan->max_users,
            'products' => $plan->max_products === 0 || $tenant->products()->count() < $plan->max_products,
            'monthly_orders' => $plan->max_monthly_orders === 0 || $this->getMonthlyOrdersCount($tenant) < $plan->max_monthly_orders,
            'tables' => $plan->max_tables === 0 || RestaurantTable::where('tenant_id', $tenant->id)->count() < $plan->max_tables,
            default => true,
        };
    }

    /**
     * Get detailed quota usage stats for a tenant.
     */
    public function getQuotaUsage(Tenant|int $tenant): array
    {
        if (is_numeric($tenant)) {
            $tenant = Tenant::findOrFail($tenant);
        }

        $plan = $tenant->currentPlan();

        $maxUsers = $plan?->max_users ?? 0;
        $maxProducts = $plan?->max_products ?? 0;
        $maxOrders = $plan?->max_monthly_orders ?? 0;
        $maxTables = $plan?->max_tables ?? 0;

        $usedUsers = $tenant->users()->count();
        $usedProducts = $tenant->products()->count();
        $usedOrders = $this->getMonthlyOrdersCount($tenant);
        $usedTables = RestaurantTable::where('tenant_id', $tenant->id)->count();

        return [
            'users' => [
                'used' => $usedUsers,
                'limit' => $maxUsers,
                'unlimited' => $maxUsers === 0 || $tenant->id === 1,
                'percentage' => $maxUsers > 0 ? min(100, round(($usedUsers / $maxUsers) * 100)) : 0,
            ],
            'products' => [
                'used' => $usedProducts,
                'limit' => $maxProducts,
                'unlimited' => $maxProducts === 0 || $tenant->id === 1,
                'percentage' => $maxProducts > 0 ? min(100, round(($usedProducts / $maxProducts) * 100)) : 0,
            ],
            'monthly_orders' => [
                'used' => $usedOrders,
                'limit' => $maxOrders,
                'unlimited' => $maxOrders === 0 || $tenant->id === 1,
                'percentage' => $maxOrders > 0 ? min(100, round(($usedOrders / $maxOrders) * 100)) : 0,
            ],
            'tables' => [
                'used' => $usedTables,
                'limit' => $maxTables,
                'unlimited' => $maxTables === 0 || $tenant->id === 1,
                'percentage' => $maxTables > 0 ? min(100, round(($usedTables / $maxTables) * 100)) : 0,
            ],
        ];
    }

    /**
     * Return all available features with effective status for this tenant.
     */
    public function getEffectiveFeatures(Tenant|int $tenant): array
    {
        if (is_numeric($tenant)) {
            $tenant = Tenant::findOrFail($tenant);
        }

        $allFeatures = PlanFeature::orderBy('id')->get();
        $overrides = TenantFeatureOverride::where('tenant_id', $tenant->id)
            ->pluck('is_enabled', 'feature_key')
            ->toArray();

        $plan = $tenant->currentPlan();
        $planFeatures = $plan?->features ?? [];
        $isWildcard = in_array('*', $planFeatures, true) || $tenant->id === 1;

        $results = [];
        foreach ($allFeatures as $feat) {
            $featKey = $feat->key ?? $feat->code;
            $hasOverride = array_key_exists($featKey, $overrides);
            $isEnabled = $hasOverride
                ? (bool) $overrides[$featKey]
                : ($isWildcard || in_array($featKey, $planFeatures, true));

            $results[] = [
                'code' => $featKey,
                'key' => $featKey,
                'name' => $feat->name,
                'group' => $feat->group,
                'description' => $feat->description,
                'is_enabled' => $isEnabled,
                'is_overridden' => $hasOverride,
                'plan_included' => $isWildcard || in_array($featKey, $planFeatures, true),
            ];
        }

        return $results;
    }

    protected function getMonthlyOrdersCount(Tenant $tenant): int
    {
        return Order::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }
}
