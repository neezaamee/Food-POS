<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // POS
            ['name' => 'Access POS', 'slug' => 'pos.access', 'module' => 'POS'],
            ['name' => 'Apply Discount', 'slug' => 'pos.discount', 'module' => 'POS'],
            ['name' => 'Price Override', 'slug' => 'pos.price-override', 'module' => 'POS'],
            ['name' => 'Cancel Order', 'slug' => 'pos.cancel-order', 'module' => 'POS'],
            ['name' => 'Refund / Sale Return', 'slug' => 'pos.refund', 'module' => 'POS'],
            ['name' => 'Credit Sale', 'slug' => 'pos.credit-sale', 'module' => 'POS'],
            ['name' => 'Table Transfer', 'slug' => 'pos.table-transfer', 'module' => 'Restaurant'],
            ['name' => 'Table Merge', 'slug' => 'pos.table-merge', 'module' => 'Restaurant'],
            ['name' => 'Delete Draft Order', 'slug' => 'orders.delete-draft', 'module' => 'Orders'],

            // Cash Drawer
            ['name' => 'Close Cash Shift', 'slug' => 'cash.shift-close', 'module' => 'Cash'],
            ['name' => 'Edit Payment', 'slug' => 'cash.edit-payment', 'module' => 'Cash'],

            // Resources & Operations
            ['name' => 'Manage Products', 'slug' => 'products.manage', 'module' => 'Resources'],
            ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'module' => 'Resources'],
            ['name' => 'Manage Tables', 'slug' => 'tables.manage', 'module' => 'Restaurant'],
            ['name' => 'Manage Delivery', 'slug' => 'delivery.manage', 'module' => 'Delivery'],
            ['name' => 'Kitchen Display', 'slug' => 'kitchen.view', 'module' => 'Restaurant'],
            ['name' => 'Manage Inventory', 'slug' => 'inventory.manage', 'module' => 'Inventory'],

            // Finance & Reports
            ['name' => 'Double Entry Accounting', 'slug' => 'accounting.access', 'module' => 'Finance'],
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'Reports'],

            // Administration
            ['name' => 'Manage Users', 'slug' => 'users.manage', 'module' => 'Admin'],
            ['name' => 'System Settings', 'slug' => 'settings.access', 'module' => 'Admin'],
            ['name' => 'FBR Digital Invoicing', 'slug' => 'fbr.access', 'module' => 'Admin'],
        ];

        $permModels = [];
        foreach ($permissions as $p) {
            $permModels[$p['slug']] = Permission::firstOrCreate(['slug' => $p['slug']], $p);
        }

        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'description' => 'Full administrative access across all modules.',
                'perms' => array_keys($permModels),
            ],
            'owner' => [
                'name' => 'Owner',
                'description' => 'Business owner with full access.',
                'perms' => array_keys($permModels),
            ],
            'manager' => [
                'name' => 'Manager',
                'description' => 'Operational manager with POS, Cash, Shift, and Report access.',
                'perms' => [
                    'pos.access', 'pos.discount', 'pos.price-override', 'pos.cancel-order',
                    'pos.refund', 'pos.credit-sale', 'pos.table-transfer', 'pos.table-merge',
                    'orders.delete-draft', 'cash.shift-close', 'cash.edit-payment',
                    'products.manage', 'customers.manage', 'tables.manage', 'delivery.manage',
                    'kitchen.view', 'inventory.manage', 'reports.view',
                ],
            ],
            'cashier' => [
                'name' => 'Cashier',
                'description' => 'Point of sale cashier responsible for billing and shift cash.',
                'perms' => [
                    'pos.access', 'pos.discount', 'pos.table-transfer', 'cash.shift-close',
                    'customers.manage',
                ],
            ],
            'waiter' => [
                'name' => 'Waiter',
                'description' => 'Dining area waiter taking table orders.',
                'perms' => [
                    'pos.access', 'pos.table-transfer', 'kitchen.view',
                ],
            ],
            'kitchen-staff' => [
                'name' => 'Kitchen Staff',
                'description' => 'Kitchen display screen operator.',
                'perms' => [
                    'kitchen.view',
                ],
            ],
            'delivery-manager' => [
                'name' => 'Delivery Manager',
                'description' => 'Manages dispatch, delivery areas, and riders.',
                'perms' => [
                    'pos.access', 'delivery.manage',
                ],
            ],
            'rider' => [
                'name' => 'Rider',
                'description' => 'Delivery rider executing food delivery.',
                'perms' => [],
            ],
            'accountant' => [
                'name' => 'Accountant',
                'description' => 'Manages general ledger, vouchers, financial statements, and reports.',
                'perms' => [
                    'accounting.access', 'reports.view', 'inventory.manage',
                ],
            ],
        ];

        foreach ($roles as $slug => $data) {
            $role = Role::firstOrCreate(['slug' => $slug], [
                'name' => $data['name'],
                'description' => $data['description'],
            ]);

            $syncIds = [];
            foreach ($data['perms'] as $permSlug) {
                if (isset($permModels[$permSlug])) {
                    $syncIds[] = $permModels[$permSlug]->id;
                }
            }
            $role->permissions()->sync($syncIds);
        }

        // Create Default Super Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@foodpoint.com'],
            [
                'name' => 'System Administrator',
                'phone' => '+92 300 1234567',
                'role' => 'super-admin',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );
        $adminRole = Role::where('slug', 'super-admin')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // Create Default Cashier User
        $cashier = User::firstOrCreate(
            ['email' => 'cashier@foodpoint.com'],
            [
                'name' => 'Front Cashier',
                'phone' => '+92 300 7654321',
                'role' => 'cashier',
                'status' => 'active',
                'password' => Hash::make('password'),
            ]
        );
        $cashierRole = Role::where('slug', 'cashier')->first();
        if ($cashierRole) {
            $cashier->roles()->syncWithoutDetaching([$cashierRole->id]);
        }
    }
}
