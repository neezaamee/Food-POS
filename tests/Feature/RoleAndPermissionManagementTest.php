<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaaS\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAndPermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $owner;

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

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Store Owner',
            'email' => 'owner@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();
        if ($ownerRole) {
            $this->owner->roles()->attach($ownerRole->id);
        }
    }

    public function test_owner_can_view_roles_list_including_system_roles(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(route('roles.index'));

        $response->assertOk();
        $response->assertViewIs('admin.roles.index');
        $response->assertSee('Branch Manager');
        $response->assertSee('Cashier');
        $response->assertSee('System Default');
    }

    public function test_owner_can_create_custom_role_with_permissions(): void
    {
        $this->actingAs($this->owner);

        $perm1 = Permission::where('slug', 'pos.access')->first();
        $perm2 = Permission::where('slug', 'tables.manage')->first();

        $response = $this->post(route('roles.store'), [
            'name' => 'Shift Supervisor',
            'description' => 'Supervises dining floor and counter',
            'permissions' => [$perm1->id, $perm2->id],
        ]);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success');

        $role = Role::where('slug', 'shift-supervisor')->first();
        $this->assertNotNull($role);
        $this->assertEquals('Shift Supervisor', $role->name);
        $this->assertEquals($this->tenant->id, $role->tenant_id);
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->hasPermission('pos.access'));
        $this->assertTrue($role->hasPermission('tables.manage'));
        $this->assertFalse($role->hasPermission('accounting.access'));

        // Verify AuditLog record was created
        $this->assertTrue(AuditLog::where('action', 'Role Created')->where('record_id', $role->id)->exists());
    }

    public function test_owner_can_update_custom_role_permissions(): void
    {
        $this->actingAs($this->owner);

        $role = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Floor Lead',
            'slug' => 'floor-lead',
            'description' => 'Initial desc',
            'is_system' => false,
        ]);

        $perm = Permission::where('slug', 'kitchen.view')->first();

        $response = $this->put(route('roles.update', $role->id), [
            'name' => 'Floor Lead Senior',
            'description' => 'Updated desc',
            'permissions' => [$perm->id],
        ]);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success');

        $role->refresh();
        $this->assertEquals('Floor Lead Senior', $role->name);
        $this->assertEquals('Updated desc', $role->description);
        $this->assertTrue($role->hasPermission('kitchen.view'));
        $this->assertFalse($role->hasPermission('pos.access'));
    }

    public function test_system_default_roles_cannot_be_deleted(): void
    {
        $this->actingAs($this->owner);

        $cashierRole = Role::where('slug', 'cashier')->firstOrFail();

        $response = $this->delete(route('roles.destroy', $cashierRole->id));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $cashierRole->id]);
    }

    public function test_custom_role_with_assigned_users_cannot_be_deleted(): void
    {
        $this->actingAs($this->owner);

        $customRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Barista',
            'slug' => 'barista',
            'is_system' => false,
        ]);

        $staffUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Barista',
            'email' => 'john.barista@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'barista',
            'status' => 'active',
        ]);
        $staffUser->roles()->attach($customRole->id);

        $response = $this->delete(route('roles.destroy', $customRole->id));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('roles', ['id' => $customRole->id]);
    }

    public function test_custom_role_without_assigned_users_can_be_deleted(): void
    {
        $this->actingAs($this->owner);

        $customRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Temporary Role',
            'slug' => 'temp-role',
            'is_system' => false,
        ]);

        $response = $this->delete(route('roles.destroy', $customRole->id));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_custom_role_can_be_assigned_to_user_in_user_management(): void
    {
        $this->actingAs($this->owner);

        $customRole = Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Inventory Auditor',
            'slug' => 'inventory-auditor',
            'is_system' => false,
        ]);
        $invPerm = Permission::where('slug', 'inventory.manage')->first();
        $customRole->permissions()->attach($invPerm->id);

        $response = $this->post(route('admin.users.store'), [
            'name' => 'Auditor Steve',
            'email' => 'steve@foodpoint.com',
            'phone' => '03001122334',
            'role' => 'inventory-auditor',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user = User::where('email', 'steve@foodpoint.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('inventory-auditor'));
        $this->assertTrue($user->hasPermission('inventory.manage'));
        $this->assertFalse($user->hasPermission('accounting.access'));
    }
}
