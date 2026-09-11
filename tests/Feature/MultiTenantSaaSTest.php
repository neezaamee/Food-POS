<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaaS\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantSaaSTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_tenant_registration_creates_tenant_trial_subscription_and_admin_user(): void
    {
        $plan = Plan::where('slug', 'professional')->first() ?? Plan::create([
            'name' => 'Professional',
            'slug' => 'professional',
            'price_monthly' => 79.00,
            'is_active' => true,
        ]);

        $response = $this->post(route('tenant.register.submit'), [
            'restaurant_name' => 'Spice Route Bistro',
            'slug' => 'spice-route',
            'name' => 'Hamza Tariq',
            'email' => 'hamza@spiceroute.com',
            'phone' => '+92 300 9876543',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'plan_id' => $plan->id,
            'currency' => 'PKR',
        ]);

        $response->assertRedirect(route('pos.index'));
        $this->assertAuthenticated();

        $tenant = Tenant::where('slug', 'spice-route')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Spice Route Bistro', $tenant->name);
        $this->assertEquals('trial', $tenant->status);

        // Verify trial subscription attached
        $sub = $tenant->activeSubscription;
        $this->assertNotNull($sub);
        $this->assertEquals('trial', $sub->status);
        $this->assertEquals($plan->id, $sub->plan_id);

        // Verify starter table and category seeded for this tenant
        $this->assertTrue(Category::where('tenant_id', $tenant->id)->exists());
    }

    public function test_multi_tenant_data_isolation_between_two_restaurants(): void
    {
        // Tenant 1 (Default: Food Point Main)
        $user1 = User::where('email', 'admin@foodpoint.com')->first();
        $this->actingAs($user1);

        $cat1 = Category::create(['name' => 'Tenant 1 Specials', 'slug' => 't1-specials', 'is_active' => true]);
        $prod1 = Product::create([
            'name' => 'T1 Exclusive Dish',
            'code' => 'T1-EXCL-01',
            'category_id' => $cat1->id,
            'sale_price' => 500.00,
            'cost_price' => 200.00,
            'is_active' => true,
        ]);

        // Tenant 2 (New Cafe)
        $tenant2 = Tenant::create([
            'name' => 'Urban Cafe',
            'slug' => 'urban-cafe',
            'email' => 'owner@urbancafe.com',
            'status' => 'active',
        ]);

        $user2 = User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Cafe Manager',
            'email' => 'manager@urbancafe.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        // Switch acting user to Tenant 2
        $this->actingAs($user2);
        TenantContext::set($tenant2);

        // Verify Tenant 2 cannot see Tenant 1's products or categories
        $this->assertFalse(Product::where('code', 'T1-EXCL-01')->exists());
        $this->assertFalse(Category::where('slug', 't1-specials')->exists());

        // Create Tenant 2 product with the SAME code (allowed in multi-tenancy!)
        $cat2 = Category::create(['name' => 'Cafe Beverages', 'slug' => 'cafe-beverages', 'is_active' => true]);
        $prod2 = Product::create([
            'name' => 'T2 Espresso',
            'code' => 'T1-EXCL-01', // Same code as Tenant 1!
            'category_id' => $cat2->id,
            'sale_price' => 350.00,
            'cost_price' => 100.00,
            'is_active' => true,
        ]);

        $this->assertEquals($tenant2->id, $prod2->tenant_id);

        // Switch back to Tenant 1
        $this->actingAs($user1);
        TenantContext::set(Tenant::find(1));

        // Tenant 1 sees their product, not Tenant 2's product
        $found = Product::where('code', 'T1-EXCL-01')->first();
        $this->assertNotNull($found);
        $this->assertEquals('T1 Exclusive Dish', $found->name);
    }

    public function test_super_admin_can_view_saas_dashboard_and_tenant_details(): void
    {
        $superAdmin = User::where('role', 'super-admin')->first();
        $this->actingAs($superAdmin);

        $response = $this->get(route('saas.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('SaaS Platform Administration');

        $tenant = Tenant::first();
        $detailResponse = $this->get(route('saas.tenants.show', $tenant));
        $detailResponse->assertStatus(200);
    }

    public function test_super_admin_can_impersonate_and_exit_tenant(): void
    {
        $superAdmin = User::where('role', 'super-admin')->first();
        $this->actingAs($superAdmin);

        $tenant = Tenant::create([
            'name' => 'Pizza Crust Co',
            'slug' => 'pizza-crust',
            'status' => 'active',
        ]);

        // 1. Initiate Impersonation
        $response = $this->get(route('saas.tenants.impersonate', $tenant));
        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(session()->has('impersonated_tenant_id'));
        $this->assertEquals($tenant->id, session('impersonated_tenant_id'));

        // 2. Exit Impersonation
        $exitResponse = $this->get(route('saas.exit-impersonation'));
        $exitResponse->assertRedirect(route('saas.tenants.index'));
        $this->assertFalse(session()->has('impersonated_tenant_id'));
    }

    public function test_subscription_product_limit_enforced(): void
    {
        $tenant = Tenant::create([
            'name' => 'Mini Kiosk',
            'slug' => 'mini-kiosk',
            'status' => 'active',
        ]);

        $starterPlan = Plan::create([
            'name' => 'Starter Limited',
            'slug' => 'starter-limited',
            'price_monthly' => 19.00,
            'max_products' => 2, // Only 2 products allowed!
            'is_active' => true,
        ]);

        $tenant->subscriptions()->create([
            'plan_id' => $starterPlan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Kiosk Admin',
            'email' => 'kiosk@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user);
        TenantContext::set($tenant);

        $cat = Category::create(['name' => 'Snacks', 'slug' => 'snacks', 'is_active' => true]);

        // 1. Create first product - Success
        Product::create([
            'name' => 'Chips',
            'code' => 'CHP-01',
            'category_id' => $cat->id,
            'sale_price' => 50,
            'is_active' => true,
        ]);

        // 2. Create second product - Success
        Product::create([
            'name' => 'Biscuits',
            'code' => 'BSC-02',
            'category_id' => $cat->id,
            'sale_price' => 40,
            'is_active' => true,
        ]);

        // 3. Attempt 3rd product via controller store route - Should be blocked by SubscriptionService
        $response = $this->post(route('resources.products.store'), [
            'name' => 'Soda',
            'code' => 'SOD-03',
            'category_id' => $cat->id,
            'sale_price' => 80,
            'cost_price' => 40,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('maximum product limit allowed', session('error'));
        $this->assertEquals(2, Product::count());
    }

    public function test_suspended_tenant_is_blocked_from_access(): void
    {
        $tenant = Tenant::create([
            'name' => 'Overdue Diner',
            'slug' => 'overdue-diner',
            'status' => 'suspended', // Suspended
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Diner Staff',
            'email' => 'staff@overdue.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        $response = $this->get(route('pos.index'));
        $response->assertStatus(403);
        $response->assertSee('Account Suspended');
    }
}
