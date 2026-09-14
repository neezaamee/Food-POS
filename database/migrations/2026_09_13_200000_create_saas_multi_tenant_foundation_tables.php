<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add missing business columns to tenants table if not present
        Schema::table('tenants', function (Blueprint $table) {
            if (! Schema::hasColumn('tenants', 'business_type')) {
                $table->string('business_type', 50)->default('restaurant')->after('name');
            }
            if (! Schema::hasColumn('tenants', 'legal_name')) {
                $table->string('legal_name', 150)->nullable()->after('business_type');
            }
            if (! Schema::hasColumn('tenants', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }
            if (! Schema::hasColumn('tenants', 'province')) {
                $table->string('province', 100)->nullable()->after('city');
            }
            if (! Schema::hasColumn('tenants', 'country')) {
                $table->string('country', 100)->default('Pakistan')->after('province');
            }
            if (! Schema::hasColumn('tenants', 'ntn')) {
                $table->string('ntn', 50)->nullable()->after('country');
            }
            if (! Schema::hasColumn('tenants', 'strn')) {
                $table->string('strn', 50)->nullable()->after('ntn');
            }
            if (! Schema::hasColumn('tenants', 'is_setup_completed')) {
                $table->boolean('is_setup_completed')->default(true)->after('status');
            }
            if (! Schema::hasColumn('tenants', 'disabled_at')) {
                $table->dateTime('disabled_at')->nullable()->after('trial_ends_at');
            }
        });

        // 2. Tenant Owners Pivot Table (Business ↔ Owner User)
        if (! Schema::hasTable('tenant_owners')) {
            Schema::create('tenant_owners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('is_primary')->default(true);
                $table->timestamps();

                $table->unique(['tenant_id', 'user_id']);
                $table->index(['tenant_id', 'is_primary']);
            });
        }

        // 3. Plan Features Catalog (Master system feature registry)
        if (! Schema::hasTable('plan_features')) {
            Schema::create('plan_features', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('key', 100)->unique();
                $table->string('group', 50)->default('general'); // sales, operations, inventory, finance, compliance, advanced
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 4. Tenant Feature Overrides (Super Admin custom feature grants per business)
        if (! Schema::hasTable('tenant_feature_overrides')) {
            Schema::create('tenant_feature_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('feature_key', 100);
                $table->boolean('is_enabled')->default(true); // true = grant, false = revoke
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'feature_key']);
            });
        }

        // 5. Seed Core System Features
        $features = [
            // Sales & POS
            ['name' => 'Point of Sale (POS)', 'key' => 'pos.core', 'group' => 'sales', 'description' => 'Fast single-page touch terminal & cart'],
            ['name' => 'Takeaway Ordering', 'key' => 'pos.takeaway', 'group' => 'sales', 'description' => 'Takeaway counter order management'],
            ['name' => 'Dine-In & Tables', 'key' => 'pos.dine_in', 'group' => 'sales', 'description' => 'Table layout, occupancy & seating assignments'],
            ['name' => 'Home Delivery & Riders', 'key' => 'pos.delivery', 'group' => 'sales', 'description' => 'Delivery zone fees, rider dispatch & mileage tracking'],
            ['name' => 'Hold / Open Orders', 'key' => 'pos.open_orders', 'group' => 'sales', 'description' => 'Hold orders and recall active bills'],

            // Kitchen Operations
            ['name' => 'Kitchen Order Tickets (KOT)', 'key' => 'kitchen.kds', 'group' => 'operations', 'description' => 'KOT routing and kitchen order ticketing'],
            ['name' => 'Urdu Menu & Kitchen Print', 'key' => 'kitchen.urdu', 'group' => 'operations', 'description' => 'Dual Urdu item names on KOT slips'],

            // Inventory & Supply Chain
            ['name' => 'Stock & Inventory Ledger', 'key' => 'inventory.stock', 'group' => 'inventory', 'description' => 'Real-time stock tracking, movements and adjustments'],
            ['name' => 'Recipe & Menu Engineering', 'key' => 'inventory.recipes', 'group' => 'inventory', 'description' => 'Ingredient recipe auto-deductions on sales'],
            ['name' => 'Supplier Purchases & Bills', 'key' => 'inventory.purchases', 'group' => 'inventory', 'description' => 'Purchase orders, stock receiving and vendor balances'],

            // Finance & Accounting
            ['name' => 'Cash Shift & Day Close', 'key' => 'finance.day_close', 'group' => 'finance', 'description' => 'Drawer sessions, cash reconciliation and Z-Report'],
            ['name' => 'Double-Entry Accounting', 'key' => 'finance.double_entry', 'group' => 'finance', 'description' => 'General Ledger, Chart of Accounts and Journal Vouchers'],

            // Compliance & Messaging
            ['name' => 'FBR Fiscalization', 'key' => 'compliance.fbr', 'group' => 'compliance', 'description' => 'Pakistan FBR POS fiscal invoice integration & QR code'],
            ['name' => 'WhatsApp Customer Receipts', 'key' => 'messaging.whatsapp', 'group' => 'messaging', 'description' => 'Direct PDF/text WhatsApp receipt dispatch'],

            // Analytics & Multi-User
            ['name' => 'Advanced Financial Reports', 'key' => 'reports.advanced', 'group' => 'advanced', 'description' => 'Trial Balance, Ledger, Product Velocity, Excel/PDF exports'],
            ['name' => 'Multi-User Shift Accounts', 'key' => 'saas.multiple_users', 'group' => 'advanced', 'description' => 'Multiple simultaneous cashiers and staff logins'],
        ];

        foreach ($features as $f) {
            DB::table('plan_features')->updateOrInsert(
                ['key' => $f['key']],
                [
                    'name' => $f['name'],
                    'group' => $f['group'],
                    'description' => $f['description'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 6. Backfill existing Owner link for Tenant #1
        $adminUser = DB::table('users')->where('id', 1)->first();
        if ($adminUser) {
            DB::table('tenant_owners')->updateOrInsert(
                ['tenant_id' => 1, 'user_id' => $adminUser->id],
                ['is_primary' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_feature_overrides');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('tenant_owners');
    }
};
