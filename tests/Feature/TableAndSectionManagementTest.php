<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\TableSection;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaaS\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableAndSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $manager;

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

        $this->manager = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Floor Manager',
            'email' => 'manager@foodpoint.com',
            'password' => bcrypt('password'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $managerRole = Role::where('slug', 'manager')->first();
        if (! $managerRole) {
            $managerRole = Role::create([
                'tenant_id' => $this->tenant->id,
                'name' => 'Manager',
                'slug' => 'manager',
            ]);
        }
        $tablePermission = Permission::firstOrCreate(['slug' => 'tables.manage'], ['name' => 'Manage Tables', 'module' => 'tables']);
        $managerRole->permissions()->syncWithoutDetaching([$tablePermission->id]);
        $this->manager->roles()->syncWithoutDetaching([$managerRole->id]);
    }

    public function test_user_can_view_dining_tables_and_sections(): void
    {
        $this->actingAs($this->manager);

        $section = TableSection::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Terrace Garden',
            'description' => 'Outdoor seating',
        ]);

        RestaurantTable::create([
            'tenant_id' => $this->tenant->id,
            'section_id' => $section->id,
            'table_number' => 'TG-01',
            'name' => 'Garden Table 1',
            'capacity' => 4,
            'status' => 'available',
        ]);

        $response = $this->get(route('restaurant.tables'));
        $response->assertOk();
        $response->assertSee('Terrace Garden');
        $response->assertSee('TG-01');
    }

    public function test_user_can_edit_restaurant_table(): void
    {
        $this->actingAs($this->manager);

        $section = TableSection::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Hall',
        ]);

        $table = RestaurantTable::create([
            'tenant_id' => $this->tenant->id,
            'section_id' => $section->id,
            'table_number' => 'EDIT-T-99',
            'name' => 'Table 1',
            'capacity' => 2,
            'status' => 'available',
        ]);

        $response = $this->put(route('restaurant.tables.update', $table->id), [
            'section_id' => $section->id,
            'table_number' => 'EDIT-T-99-VIP',
            'name' => 'VIP Table 1',
            'capacity' => 6,
            'status' => 'reserved',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'table_number' => 'EDIT-T-99-VIP',
            'name' => 'VIP Table 1',
            'capacity' => 6,
            'status' => 'reserved',
        ]);
    }

    public function test_user_can_delete_restaurant_table_when_not_occupied(): void
    {
        $this->actingAs($this->manager);

        $section = TableSection::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Rooftop',
        ]);

        $table = RestaurantTable::create([
            'tenant_id' => $this->tenant->id,
            'section_id' => $section->id,
            'table_number' => 'RT-05',
            'name' => 'Rooftop Table 5',
            'capacity' => 4,
            'status' => 'available',
        ]);

        $response = $this->delete(route('restaurant.tables.destroy', $table->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('tables', [
            'id' => $table->id,
        ]);
    }

    public function test_user_cannot_delete_occupied_restaurant_table(): void
    {
        $this->actingAs($this->manager);

        $section = TableSection::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'VIP Area',
        ]);

        $table = RestaurantTable::create([
            'tenant_id' => $this->tenant->id,
            'section_id' => $section->id,
            'table_number' => 'VIP-10',
            'name' => 'VIP Booth',
            'capacity' => 8,
            'status' => 'occupied',
            'active_order_id' => 999,
        ]);

        $response = $this->delete(route('restaurant.tables.destroy', $table->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
        ]);
    }

    public function test_user_can_update_and_delete_empty_table_section(): void
    {
        $this->actingAs($this->manager);

        $section = TableSection::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Old Lounge',
            'description' => 'To be renovated',
        ]);

        $response = $this->put(route('restaurant.sections.update', $section->id), [
            'name' => 'New Premium Lounge',
            'description' => 'Renovation completed',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('table_sections', [
            'id' => $section->id,
            'name' => 'New Premium Lounge',
        ]);

        $deleteResponse = $this->delete(route('restaurant.sections.destroy', $section->id));
        $deleteResponse->assertRedirect();
        $deleteResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('table_sections', [
            'id' => $section->id,
        ]);
    }
}
