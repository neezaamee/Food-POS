<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaaS\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SaasAdminController extends Controller
{
    /**
     * SaaS Super-Admin Platform Dashboard Overview
     */
    public function dashboard()
    {
        return TenantContext::instance()->withoutTenant(function () {
            $totalTenants = Tenant::count();
            $activeTenants = Tenant::where('status', 'active')->count();
            $trialTenants = Tenant::where('status', 'trial')->count();
            $suspendedTenants = Tenant::where('status', 'suspended')->count();

            $totalSubscriptions = Subscription::count();
            $activeSubscriptions = Subscription::where('status', 'active')->count();

            $totalOrders = Order::withoutGlobalScopes()->count();
            $totalPlatformSales = (float) Order::withoutGlobalScopes()->where('order_status', 'completed')->sum('grand_total');

            // Estimated Monthly Recurring Revenue (MRR)
            $mrr = Subscription::where('status', 'active')
                ->with('plan')
                ->get()
                ->sum(fn ($sub) => $sub->plan?->price_monthly ?? 0);

            $recentTenants = Tenant::with(['subscriptions' => fn ($q) => $q->latest()->with('plan')])
                ->latest()
                ->take(8)
                ->get();

            $recentSubscriptions = Subscription::with(['tenant', 'plan'])
                ->latest()
                ->take(8)
                ->get();

            return view('saas.admin.index', compact(
                'totalTenants',
                'activeTenants',
                'trialTenants',
                'suspendedTenants',
                'totalSubscriptions',
                'activeSubscriptions',
                'totalOrders',
                'totalPlatformSales',
                'mrr',
                'recentTenants',
                'recentSubscriptions'
            ));
        });
    }

    /**
     * Tenants Directory
     */
    public function tenants(Request $request)
    {
        return TenantContext::instance()->withoutTenant(function () use ($request) {
            $query = Tenant::with(['subscriptions' => fn ($q) => $q->latest()->with('plan')]);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            $tenants = $query->latest()->paginate(15)->withQueryString();
            $plans = Plan::where('is_active', true)->get();

            return view('saas.admin.tenants', compact('tenants', 'plans'));
        });
    }

    /**
     * Detailed Tenant View
     */
    public function tenantDetails(Tenant $tenant)
    {
        return TenantContext::instance()->withoutTenant(function () use ($tenant) {
            $tenant->load(['subscriptions.plan', 'subscriptions.invoices']);

            $users = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get();
            $productsCount = Product::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
            $ordersCount = Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
            $salesTotal = (float) Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('order_status', 'completed')->sum('grand_total');

            $currentSubscription = $tenant->activeSubscription ?? $tenant->subscriptions()->latest()->first();
            $allPlans = Plan::where('is_active', true)->get();

            return view('saas.admin.tenant_details', compact(
                'tenant',
                'users',
                'productsCount',
                'ordersCount',
                'salesTotal',
                'currentSubscription',
                'allPlans'
            ));
        });
    }

    /**
     * Update Tenant Status (Active / Suspended / Trial)
     */
    public function updateTenantStatus(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,trial,cancelled',
        ]);

        $tenant->update(['status' => $validated['status']]);

        return back()->with('success', "Tenant status updated to '{$validated['status']}'.");
    }

    /**
     * Assign or Upgrade Tenant Subscription Plan
     */
    public function updateTenantPlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:active,trial,past_due,cancelled',
            'extend_days' => 'nullable|integer|min:1|max:365',
        ]);

        $plan = Plan::findOrFail($validated['plan_id']);

        $startsAt = now();
        $endsAt = $validated['extend_days']
            ? now()->addDays((int) $validated['extend_days'])
            : ($plan->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth());

        $trialEndsAt = $validated['status'] === 'trial' ? ($validated['extend_days'] ? now()->addDays((int) $validated['extend_days']) : now()->addDays(14)) : null;

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => $validated['status'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'trial_ends_at' => $trialEndsAt,
            ]
        );

        return back()->with('success', "Tenant subscription updated to '{$plan->name}' successfully.");
    }

    /**
     * Plan Management Directory
     */
    public function plans()
    {
        $plans = Plan::withCount('subscriptions')->orderBy('price')->get();

        return view('saas.admin.plans', compact('plans'));
    }

    /**
     * Store New Subscription Plan
     */
    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'max_products' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:0',
            'max_orders_per_month' => 'nullable|integer|min:0',
            'max_tables' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'description' => 'nullable|string|max:500',
        ]);

        $data = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'price_monthly' => $validated['price'],
            'price_yearly' => $validated['price'] * 10,
            'is_active' => $request->boolean('is_active', true),
            'max_products' => $validated['max_products'] ?? 0,
            'max_users' => $validated['max_users'] ?? 0,
            'max_monthly_orders' => $validated['max_orders_per_month'] ?? 0,
            'max_tables' => $validated['max_tables'] ?? 0,
            'features' => $request->input('features', []),
        ];

        Plan::create($data);

        return back()->with('success', "Plan '{$validated['name']}' created successfully.");
    }

    /**
     * Update Subscription Plan
     */
    public function updatePlan(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
            'max_products' => 'nullable|integer|min:0',
            'max_users' => 'nullable|integer|min:0',
            'max_orders_per_month' => 'nullable|integer|min:0',
            'max_tables' => 'nullable|integer|min:0',
            'features' => 'nullable|array',
            'description' => 'nullable|string|max:500',
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_monthly' => $validated['price'],
            'price_yearly' => $validated['price'] * 10,
            'is_active' => $request->boolean('is_active'),
            'max_products' => $validated['max_products'] ?? 0,
            'max_users' => $validated['max_users'] ?? 0,
            'max_monthly_orders' => $validated['max_orders_per_month'] ?? 0,
            'max_tables' => $validated['max_tables'] ?? 0,
            'features' => $request->input('features', []),
        ];

        $plan->update($data);

        return back()->with('success', "Plan '{$plan->name}' updated successfully.");
    }

    /**
     * Impersonate Tenant (Super Admin One-Click Login)
     */
    public function impersonate(Tenant $tenant)
    {
        TenantContext::instance()->startImpersonation($tenant);

        return redirect()->route('dashboard')->with('success', "Impersonation active: Viewing as '{$tenant->name}'.");
    }

    /**
     * Exit Tenant Impersonation
     */
    public function exitImpersonation()
    {
        TenantContext::instance()->exitImpersonation();

        return redirect()->route('saas.tenants.index')->with('success', 'Exited tenant impersonation. Returned to SaaS Super Admin.');
    }
}
