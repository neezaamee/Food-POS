<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantFeatureOverride;
use App\Models\User;
use App\Services\SaaS\FeatureAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasMultiTenantFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_super_admin_can_access_saas_dashboard_and_screens(): void
    {
        $superAdmin = User::where('role', 'super-admin')->first()
            ?? User::factory()->create(['role' => 'super-admin', 'tenant_id' => null]);

        $response = $this->actingAs($superAdmin)->get(route('saas.dashboard'));
        $response->assertStatus(200);

        $tenantsResponse = $this->actingAs($superAdmin)->get(route('saas.tenants.index'));
        $tenantsResponse->assertStatus(200);

        $ownersResponse = $this->actingAs($superAdmin)->get(route('saas.owners.index'));
        $ownersResponse->assertStatus(200);

        $plansResponse = $this->actingAs($superAdmin)->get(route('saas.plans.index'));
        $plansResponse->assertStatus(200);
    }

    public function test_super_admin_can_create_business_with_owner_and_plan(): void
    {
        $superAdmin = User::where('role', 'super-admin')->first()
            ?? User::factory()->create(['role' => 'super-admin', 'tenant_id' => null]);

        $plan = Plan::first() ?? Plan::create([
            'name' => 'Standard Tier',
            'slug' => 'standard-tier',
            'price_monthly' => 30,
            'is_active' => true,
            'features' => ['pos.terminal', 'pos.dine_in'],
        ]);

        $uniqueEmail = 'testowner_'.uniqid().'@example.com';
        $businessName = 'Pizza Palazzo '.uniqid();

        $response = $this->actingAs($superAdmin)->post(route('saas.tenants.store'), [
            'name' => $businessName,
            'business_type' => 'restaurant',
            'email' => $uniqueEmail,
            'phone' => '+92 300 9876543',
            'city' => 'Lahore',
            'province' => 'Punjab',
            'address' => 'Gulberg III, MM Alam Road',
            'currency' => 'PKR',
            'plan_id' => $plan->id,
            'status' => 'active',
            'owner_name' => 'Tariq Mehmood',
            'owner_email' => $uniqueEmail,
            'owner_phone' => '+92 300 9876543',
            'owner_password' => 'secret1234',
        ]);

        $response->assertRedirect();

        $tenant = Tenant::where('name', $businessName)->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('active', $tenant->status);

        $owner = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($owner);
        $this->assertEquals('owner', $owner->role);
        $this->assertEquals($tenant->id, $owner->tenant_id);

        // Verify pivot relationship
        $this->assertTrue($tenant->owners()->where('users.id', $owner->id)->exists());
        $this->assertEquals($owner->id, $tenant->primaryOwner()?->id);
    }

    public function test_feature_access_service_evaluates_plan_and_overrides(): void
    {
        $tenant = Tenant::create([
            'name' => 'Test Cafe '.uniqid(),
            'slug' => 'test-cafe-'.uniqid(),
            'status' => 'active',
            'currency' => 'PKR',
        ]);

        $plan = Plan::create([
            'name' => 'Basic Tier',
            'slug' => 'basic-tier-'.uniqid(),
            'price_monthly' => 15,
            'features' => ['pos.terminal'],
        ]);

        $tenant->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $service = app(FeatureAccessService::class);

        // Base plan has pos.terminal, but not inventory.management
        $this->assertTrue($service->allows($tenant, 'pos.terminal'));
        $this->assertFalse($service->allows($tenant, 'inventory.management'));

        // Apply custom Super Admin override to enable inventory
        TenantFeatureOverride::create([
            'tenant_id' => $tenant->id,
            'feature_code' => 'inventory.management',
            'is_enabled' => true,
        ]);

        $this->assertTrue($service->allows($tenant, 'inventory.management'));

        // When tenant is suspended, all features are blocked
        $tenant->update(['status' => 'suspended']);
        $this->assertFalse($service->allows($tenant, 'pos.terminal'));
        $this->assertFalse($service->allows($tenant, 'inventory.management'));
    }

    public function test_suspended_and_disabled_tenants_are_locked_out(): void
    {
        $tenant = Tenant::create([
            'name' => 'Locked Cafe '.uniqid(),
            'slug' => 'locked-cafe-'.uniqid(),
            'status' => 'suspended',
            'currency' => 'PKR',
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Locked Owner',
            'email' => 'locked_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $response = $this->actingAs($owner)->get(route('dashboard'));
        $response->assertStatus(403);
    }
}
