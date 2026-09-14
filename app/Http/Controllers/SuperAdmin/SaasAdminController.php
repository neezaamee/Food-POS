<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantFeatureOverride;
use App\Models\User;
use App\Services\SaaS\FeatureAccessService;
use App\Services\SaaS\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SaasAdminController extends Controller
{
    public function __construct(
        protected FeatureAccessService $featureAccessService
    ) {}

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
            $disabledTenants = Tenant::where('status', 'disabled')->count();

            $totalSubscriptions = Subscription::count();
            $activeSubscriptions = Subscription::where('status', 'active')->count();

            $totalOrders = Order::withoutGlobalScopes()->count();
            $totalPlatformSales = (float) Order::withoutGlobalScopes()->where('order_status', 'completed')->sum('grand_total');

            // Estimated Monthly Recurring Revenue (MRR)
            $mrr = Subscription::where('status', 'active')
                ->with('plan')
                ->get()
                ->sum(fn ($sub) => $sub->plan?->price_monthly ?? 0);

            $recentTenants = Tenant::with(['subscriptions' => fn ($q) => $q->latest()->with('plan'), 'owners'])
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
                'disabledTenants',
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
            $query = Tenant::with([
                'subscriptions' => fn ($q) => $q->latest()->with('plan'),
                'owners',
            ]);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('business_type')) {
                $query->where('business_type', $request->business_type);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            }

            $tenants = $query->latest()->paginate(15)->withQueryString();
            $plans = Plan::where('is_active', true)->get();

            return view('saas.admin.tenants', compact('tenants', 'plans'));
        });
    }

    /**
     * Store New Business (Tenant) with Business Owner and Plan in one transaction
     */
    public function storeTenant(Request $request)
    {
        $validated = $request->validate([
            // Business Details
            'name' => 'required|string|max:150',
            'business_type' => 'required|string|in:restaurant,cafe,fast_food,bakery,food_truck,cloud_kitchen,ice_cream,juice_bar,other',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'currency' => 'required|string|max:10',
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:active,trial,suspended',

            // Owner Account Details
            'owner_name' => 'required|string|max:150',
            'owner_email' => 'required|email|max:150|unique:users,email',
            'owner_phone' => 'nullable|string|max:50',
            'owner_password' => 'required|string|min:6',
        ]);

        return DB::transaction(function () use ($validated) {
            // Generate unique slug
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter++;
            }

            $isTrial = $validated['status'] === 'trial';
            $trialEndsAt = $isTrial ? now()->addDays(14) : null;

            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'business_type' => $validated['business_type'],
                'legal_name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? $validated['owner_email'],
                'city' => $validated['city'] ?? null,
                'province' => $validated['province'] ?? null,
                'address' => $validated['address'] ?? null,
                'currency' => $validated['currency'],
                'timezone' => 'Asia/Karachi',
                'status' => $validated['status'],
                'trial_ends_at' => $trialEndsAt,
                'is_setup_completed' => true,
            ]);

            // 2. Create Owner User
            $owner = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'phone' => $validated['owner_phone'] ?? null,
                'password' => Hash::make($validated['owner_password']),
                'role' => 'owner',
                'status' => 'active',
            ]);

            // 3. Link via tenant_owners pivot
            $tenant->owners()->attach($owner->id, ['is_primary' => true]);

            // 4. Assign Plan Subscription
            $plan = Plan::findOrFail($validated['plan_id']);
            $startsAt = now();
            $endsAt = $isTrial ? now()->addDays(14) : now()->addMonth();

            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $isTrial ? 'trial' : 'active',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'trial_ends_at' => $trialEndsAt,
            ]);

            return redirect()->route('saas.tenants.show', $tenant)
                ->with('success', "Business '{$tenant->name}' created successfully with Owner '{$owner->name}'.");
        });
    }

    /**
     * Platform Owners Directory
     */
    public function owners(Request $request)
    {
        return TenantContext::instance()->withoutTenant(function () use ($request) {
            $query = User::where('role', 'owner')
                ->orWhereHas('ownedTenants')
                ->with(['ownedTenants', 'tenant']);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $owners = $query->latest()->paginate(15)->withQueryString();
            $allTenants = Tenant::orderBy('name')->get();

            return view('saas.admin.owners', compact('owners', 'allTenants'));
        });
    }

    /**
     * Store New Owner and Assign to Business
     */
    public function storeOwner(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'required|string|min:6',
            'is_primary' => 'nullable|boolean',
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $isPrimary = $request->boolean('is_primary', false);

        if ($isPrimary) {
            // Remove primary flag from existing owners
            DB::table('tenant_owners')->where('tenant_id', $tenant->id)->update(['is_primary' => false]);
        }

        $tenant->owners()->syncWithoutDetaching([
            $owner->id => ['is_primary' => $isPrimary],
        ]);

        return back()->with('success', "Owner '{$owner->name}' successfully added and assigned to {$tenant->name}.");
    }

    /**
     * Detailed Tenant View with Quota, Plan, Overrides, and Owners
     */
    public function tenantDetails(Tenant $tenant)
    {
        return TenantContext::instance()->withoutTenant(function () use ($tenant) {
            $tenant->load(['subscriptions.plan', 'subscriptions.invoices', 'owners', 'featureOverrides']);

            $users = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get();
            $productsCount = Product::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
            $ordersCount = Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
            $salesTotal = (float) Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('order_status', 'completed')->sum('grand_total');

            $currentSubscription = $tenant->activeSubscription ?? $tenant->subscriptions()->latest()->first();
            $allPlans = Plan::where('is_active', true)->get();

            // Quota usage & effective feature statuses
            $quotaUsage = $this->featureAccessService->getQuotaUsage($tenant);
            $effectiveFeatures = $this->featureAccessService->getEffectiveFeatures($tenant);
            $allFeatures = PlanFeature::where('is_active', true)->orderBy('group')->orderBy('id')->get();

            return view('saas.admin.tenant_details', compact(
                'tenant',
                'users',
                'productsCount',
                'ordersCount',
                'salesTotal',
                'currentSubscription',
                'allPlans',
                'quotaUsage',
                'effectiveFeatures',
                'allFeatures'
            ));
        });
    }

    /**
     * Update Tenant Status (Active / Suspended / Disabled / Trial)
     */
    public function updateTenantStatus(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,disabled,trial',
            'reason' => 'nullable|string|max:255',
        ]);

        $status = $validated['status'];
        $disabledAt = $status === 'disabled' ? now() : null;

        $tenant->update([
            'status' => $status,
            'disabled_at' => $disabledAt,
        ]);

        $statusLabel = ucfirst($status);

        return back()->with('success', "Business '{$tenant->name}' status updated to {$statusLabel}.");
    }

    /**
     * Update Tenant Feature Overrides (Super Admin Granular Feature Switch)
     */
    public function updateTenantFeatures(Request $request, Tenant $tenant)
    {
        $overrides = $request->input('overrides', []); // array of feature_key => 'enable' | 'disable' | 'inherit'

        foreach ($overrides as $featureKey => $action) {
            if ($action === 'inherit') {
                TenantFeatureOverride::where('tenant_id', $tenant->id)
                    ->where('feature_key', $featureKey)
                    ->delete();
            } elseif ($action === 'enable') {
                TenantFeatureOverride::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'feature_key' => $featureKey],
                    ['is_enabled' => true, 'notes' => 'Enabled by Super Admin override']
                );
            } elseif ($action === 'disable') {
                TenantFeatureOverride::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'feature_key' => $featureKey],
                    ['is_enabled' => false, 'notes' => 'Disabled by Super Admin override']
                );
            }
        }

        return back()->with('success', "Feature overrides for '{$tenant->name}' saved successfully.");
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

        $trialEndsAt = $validated['status'] === 'trial'
            ? ($validated['extend_days'] ? now()->addDays((int) $validated['extend_days']) : now()->addDays(14))
            : null;

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
        $plans = Plan::withCount('subscriptions')->orderBy('price_monthly')->get();
        $allFeatures = PlanFeature::where('is_active', true)->orderBy('group')->orderBy('id')->get();

        return view('saas.admin.plans', compact('plans', 'allFeatures'));
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
            'is_active' => 'nullable|boolean',
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
     * Toggle Plan Active / Disabled Status
     */
    public function togglePlanStatus(Plan $plan)
    {
        $plan->update(['is_active' => ! $plan->is_active]);
        $status = $plan->is_active ? 'enabled' : 'disabled';

        return back()->with('success', "Plan '{$plan->name}' has been {$status}.");
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
