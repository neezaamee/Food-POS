<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // POS Terminal & Sales Operations
            ['name' => 'Access POS Terminal', 'slug' => 'pos.access', 'module' => 'POS'],
            ['name' => 'Apply Discount', 'slug' => 'pos.discount', 'module' => 'POS'],
            ['name' => 'Price Override', 'slug' => 'pos.price-override', 'module' => 'POS'],
            ['name' => 'Cancel Order', 'slug' => 'pos.cancel-order', 'module' => 'POS'],
            ['name' => 'Refund / Sale Return', 'slug' => 'pos.refund', 'module' => 'POS'],
            ['name' => 'Credit Sale', 'slug' => 'pos.credit-sale', 'module' => 'POS'],
            ['name' => 'Table Transfer', 'slug' => 'pos.table-transfer', 'module' => 'Restaurant'],
            ['name' => 'Table Merge', 'slug' => 'pos.table-merge', 'module' => 'Restaurant'],

            // Orders & Returns
            ['name' => 'View Orders / Invoices', 'slug' => 'orders.view', 'module' => 'Orders'],
            ['name' => 'Delete Draft Order', 'slug' => 'orders.delete-draft', 'module' => 'Orders'],
            ['name' => 'Manage Sale Returns', 'slug' => 'orders.returns', 'module' => 'Orders'],

            // Cash Drawer & Shifts
            ['name' => 'Cash Shifts Access', 'slug' => 'cash.shifts', 'module' => 'Cash'],
            ['name' => 'Close Cash Shift', 'slug' => 'cash.shift-close', 'module' => 'Cash'],
            ['name' => 'Edit Cash Payment', 'slug' => 'cash.edit-payment', 'module' => 'Cash'],
            ['name' => 'Day Close & Z-Report', 'slug' => 'cash.day-close', 'module' => 'Cash'],

            // Restaurant & Floor Operations
            ['name' => 'Manage Tables & Sections', 'slug' => 'tables.manage', 'module' => 'Restaurant'],
            ['name' => 'Kitchen Display (KOT)', 'slug' => 'kitchen.view', 'module' => 'Restaurant'],
            ['name' => 'Manage Delivery & Riders', 'slug' => 'delivery.manage', 'module' => 'Delivery'],

            // Menu & Catalog
            ['name' => 'Manage Products & Recipes', 'slug' => 'products.manage', 'module' => 'Catalog'],
            ['name' => 'Manage Packages & Deals', 'slug' => 'deals.manage', 'module' => 'Catalog'],
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'module' => 'Catalog'],
            ['name' => 'Manage Customers', 'slug' => 'customers.manage', 'module' => 'Catalog'],

            // Inventory & Purchasing
            ['name' => 'Manage Inventory', 'slug' => 'inventory.manage', 'module' => 'Inventory'],
            ['name' => 'Manage Purchases', 'slug' => 'purchases.manage', 'module' => 'Inventory'],
            ['name' => 'Stock Adjustments', 'slug' => 'adjustments.manage', 'module' => 'Inventory'],

            // Finance & Accounts
            ['name' => 'Double Entry Accounting', 'slug' => 'accounting.access', 'module' => 'Finance'],

            // Analytics & Reports
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'Reports'],

            // Administration & Security
            ['name' => 'Manage Users & Staff', 'slug' => 'users.manage', 'module' => 'Admin'],
            ['name' => 'Manage Roles & Permissions', 'slug' => 'roles.manage', 'module' => 'Admin'],
            ['name' => 'System Settings', 'slug' => 'settings.access', 'module' => 'Admin'],
            ['name' => 'FBR Digital Invoicing', 'slug' => 'fbr.access', 'module' => 'Admin'],
            ['name' => 'View Audit Trail', 'slug' => 'audit.view', 'module' => 'Admin'],
        ];

        $permModels = [];
        foreach ($permissions as $p) {
            $permModels[$p['slug']] = Permission::updateOrCreate(['slug' => $p['slug']], $p);
        }

        $allPermSlugs = array_keys($permModels);

        $roles = [
            'super-admin' => [
                'name' => 'Super Admin',
                'description' => 'Platform administrator with unrestricted global access.',
                'perms' => $allPermSlugs,
            ],
            'owner' => [
                'name' => 'Store Owner',
                'description' => 'Business owner with complete administrative and operational control.',
                'perms' => $allPermSlugs,
            ],
            'admin' => [
                'name' => 'Store Administrator',
                'description' => 'Full administrative access within the restaurant branch.',
                'perms' => $allPermSlugs,
            ],
            'manager' => [
                'name' => 'Branch Manager',
                'description' => 'Operational manager supervising POS, cash drawer, inventory, staff, and reports.',
                'perms' => [
                    'pos.access', 'pos.discount', 'pos.price-override', 'pos.cancel-order',
                    'pos.refund', 'pos.credit-sale', 'pos.table-transfer', 'pos.table-merge',
                    'orders.view', 'orders.delete-draft', 'orders.returns',
                    'cash.shifts', 'cash.shift-close', 'cash.edit-payment', 'cash.day-close',
                    'tables.manage', 'kitchen.view', 'delivery.manage',
                    'products.manage', 'deals.manage', 'categories.manage', 'customers.manage',
                    'inventory.manage', 'purchases.manage', 'adjustments.manage',
                    'reports.view', 'users.manage',
                ],
            ],
            'cashier' => [
                'name' => 'Cashier',
                'description' => 'Point of sale billing, shift cash management, and order punching.',
                'perms' => [
                    'pos.access', 'pos.discount', 'pos.table-transfer',
                    'orders.view', 'cash.shifts', 'cash.shift-close',
                    'customers.manage',
                ],
            ],
            'waiter' => [
                'name' => 'Waiter / Captain',
                'description' => 'Table order punching and kitchen KOT coordination.',
                'perms' => [
                    'pos.access', 'pos.table-transfer', 'kitchen.view', 'orders.view',
                ],
            ],
            'kitchen-staff' => [
                'name' => 'Kitchen Staff',
                'description' => 'Kitchen display screen and order preparation operator.',
                'perms' => [
                    'kitchen.view',
                ],
            ],
            'delivery-manager' => [
                'name' => 'Delivery Dispatcher',
                'description' => 'Manages dispatch, delivery zones, and riders.',
                'perms' => [
                    'pos.access', 'orders.view', 'delivery.manage',
                ],
            ],
            'rider' => [
                'name' => 'Delivery Rider',
                'description' => 'Executes external delivery dispatches.',
                'perms' => [],
            ],
            'accountant' => [
                'name' => 'Accountant',
                'description' => 'Manages general ledger, vouchers, financial statements, and reports.',
                'perms' => [
                    'accounting.access', 'reports.view', 'inventory.manage',
                    'purchases.manage', 'orders.view',
                ],
            ],
        ];

        foreach ($roles as $slug => $data) {
            $role = Role::updateOrCreate(
                ['slug' => $slug, 'tenant_id' => null],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_system' => true,
                ]
            );

            $syncIds = [];
            foreach ($data['perms'] as $permSlug) {
                if (isset($permModels[$permSlug])) {
                    $syncIds[] = $permModels[$permSlug]->id;
                }
            }
            $role->permissions()->sync($syncIds);
        }

        // Ensure default tenant exists for seeding context
        $defaultTenant = Tenant::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Food Point Main',
                'slug' => 'food-point-main',
                'email' => 'admin@foodpoint.com',
                'phone' => '+92 300 1234567',
                'currency' => 'Rs.',
                'timezone' => 'Asia/Karachi',
                'status' => 'active',
            ]
        );

        // Create Default Super Admin User (tenant_id = null or 1)
        $admin = User::firstOrCreate(
            ['email' => 'admin@foodpoint.com'],
            [
                'tenant_id' => $defaultTenant->id,
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

        // Create Default Cashier User associated with Tenant 1
        $cashier = User::firstOrCreate(
            ['email' => 'cashier@foodpoint.com'],
            [
                'tenant_id' => $defaultTenant->id,
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
