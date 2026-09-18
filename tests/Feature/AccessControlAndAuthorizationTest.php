<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosScreen;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaaS\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class AccessControlAndAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $superAdmin;

    protected User $owner;

    protected User $cashier;

    protected User $accountant;

    protected User $kitchenStaff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->tenant = Tenant::first() ?? Tenant::create([
            'id' => 1,
            'name' => 'Food Point Main',
            'slug' => 'food-point-main',
            'email' => 'admin@foodpoint.com',
            'phone' => '+92 300 1234567',
            'currency' => 'Rs.',
            'timezone' => 'Asia/Karachi',
            'status' => 'active',
        ]);

        TenantContext::set($this->tenant);

        // Super Admin
        $this->superAdmin = User::where('email', 'admin@foodpoint.com')->first();

        // Store Owner
        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Store Owner',
            'email' => 'owner.test@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);
        $ownerRole = Role::where('slug', 'owner')->first();
        if ($ownerRole) {
            $this->owner->roles()->attach($ownerRole->id);
        }

        // Cashier (has pos.access, pos.discount, cash.shifts, customers.manage)
        $this->cashier = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Cashier',
            'email' => 'cashier.test@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'status' => 'active',
        ]);
        $cashierRole = Role::where('slug', 'cashier')->first();
        if ($cashierRole) {
            $this->cashier->roles()->attach($cashierRole->id);
        }

        // Accountant (has accounting.access, reports.view, inventory.manage)
        $this->accountant = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Accountant',
            'email' => 'accountant.test@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'accountant',
            'status' => 'active',
        ]);
        $accountantRole = Role::where('slug', 'accountant')->first();
        if ($accountantRole) {
            $this->accountant->roles()->attach($accountantRole->id);
        }

        // Kitchen Staff (has kitchen.view)
        $this->kitchenStaff = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Kitchen Staff',
            'email' => 'kitchen.test@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'kitchen-staff',
            'status' => 'active',
        ]);
        $kitchenRole = Role::where('slug', 'kitchen-staff')->first();
        if ($kitchenRole) {
            $this->kitchenStaff->roles()->attach($kitchenRole->id);
        }
    }

    public function test_super_admin_bypasses_all_permission_checks(): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertTrue(Gate::allows('pos.access'));
        $this->assertTrue(Gate::allows('accounting.access'));
        $this->assertTrue(Gate::allows('roles.manage'));
        $this->assertTrue($this->superAdmin->hasPermission('any.random.slug'));

        $this->get(route('admin.users'))->assertOk();
        $this->get(route('roles.index'))->assertOk();
        $this->get(route('finance.chart-of-accounts'))->assertOk();
    }

    public function test_store_owner_bypasses_store_permission_checks(): void
    {
        $this->actingAs($this->owner);

        $this->assertTrue(Gate::allows('pos.access'));
        $this->assertTrue(Gate::allows('accounting.access'));
        $this->assertTrue(Gate::allows('roles.manage'));

        $this->get(route('admin.users'))->assertOk();
        $this->get(route('roles.index'))->assertOk();
        $this->get(route('finance.chart-of-accounts'))->assertOk();
    }

    public function test_cashier_can_access_pos_and_shifts_but_forbidden_from_admin_and_finance(): void
    {
        $this->actingAs($this->cashier);

        // Allowed endpoints
        $this->get(route('pos.index'))->assertOk();
        $this->get(route('cash.shifts'))->assertOk();

        // Forbidden endpoints
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('admin.users'))->assertForbidden();
        $this->get(route('finance.chart-of-accounts'))->assertForbidden();
        $this->get(route('inventory.overview'))->assertForbidden();
        $this->get(route('admin.settings'))->assertForbidden();
    }

    public function test_accountant_can_access_finance_and_reports_but_forbidden_from_admin_and_pos_cancellation(): void
    {
        $this->actingAs($this->accountant);

        // Allowed endpoints
        $this->get(route('finance.chart-of-accounts'))->assertOk();
        $this->get(route('reports.sales'))->assertOk();
        $this->get(route('inventory.overview'))->assertOk();

        // Forbidden endpoints
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('admin.users'))->assertForbidden();
        $this->get(route('admin.settings'))->assertForbidden();
    }

    public function test_kitchen_staff_can_access_kitchen_kot_but_forbidden_from_pos_and_finance(): void
    {
        $this->actingAs($this->kitchenStaff);

        // Allowed
        $this->get(route('restaurant.kitchen'))->assertOk();

        // Forbidden
        $this->get(route('pos.index'))->assertForbidden();
        $this->get(route('finance.chart-of-accounts'))->assertForbidden();
        $this->get(route('roles.index'))->assertForbidden();
    }

    public function test_custom_role_with_specific_permission_grants_access_only_to_designated_module(): void
    {
        $customRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sales Analyst',
            'slug' => 'sales-analyst',
            'is_system' => false,
        ]);
        $reportsPerm = Permission::where('slug', 'reports.view')->first();
        $customRole->permissions()->attach($reportsPerm->id);

        $analyst = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Analyst Jack',
            'email' => 'jack@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'sales-analyst',
            'status' => 'active',
        ]);
        $analyst->roles()->attach($customRole->id);

        $this->actingAs($analyst);

        // Reports is allowed
        $this->get(route('reports.sales'))->assertOk();

        // POS, Finance, and Administration are forbidden
        $this->get(route('pos.index'))->assertForbidden();
        $this->get(route('finance.chart-of-accounts'))->assertForbidden();
        $this->get(route('admin.users'))->assertForbidden();
    }

    public function test_dynamic_gate_correctly_authorizes_granted_and_denied_permissions(): void
    {
        // Cashier has pos.access, not accounting.access
        $this->assertTrue($this->cashier->can('pos.access'));
        $this->assertFalse($this->cashier->can('accounting.access'));
        $this->assertFalse($this->cashier->can('non_existent_permission_slug'));

        // Accountant has accounting.access, not pos.discount
        $this->assertTrue($this->accountant->can('accounting.access'));
        $this->assertFalse($this->accountant->can('pos.discount'));
    }

    public function test_pos_discount_authorization_enforced(): void
    {
        // Waiter does not have pos.discount permission
        $waiter = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Waiter',
            'email' => 'waiter.test@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'waiter',
            'status' => 'active',
        ]);
        $waiterRole = Role::where('slug', 'waiter')->first();
        if ($waiterRole) {
            $waiter->roles()->attach($waiterRole->id);
        }

        $this->actingAs($waiter);
        $this->assertFalse($waiter->can('pos.discount'));

        Livewire::test(PosScreen::class)
            ->set('discountRate', 100)
            ->assertSet('discountRate', 0);
    }
}
