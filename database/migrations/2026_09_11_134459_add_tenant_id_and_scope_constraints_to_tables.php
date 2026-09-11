<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * All tables requiring tenant_id scoping
     */
    protected array $tenantTables = [
        'categories',
        'brands',
        'units',
        'products',
        'deals',
        'deal_items',
        'recipe_items',
        'customers',
        'table_sections',
        'tables',
        'delivery_areas',
        'delivery_riders',
        'orders',
        'order_items',
        'order_payments',
        'kots',
        'kot_items',
        'stock_movements',
        'stock_adjustments',
        'purchases',
        'purchase_items',
        'sale_returns',
        'sale_return_items',
        'accounts',
        'journal_entries',
        'journal_entry_lines',
        'cash_shifts',
        'cash_transactions',
        'day_closes',
        'system_settings',
        'fbr_settings',
        'fbr_submissions',
        'audit_logs',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add tenant_id to users table (nullable for platform super-admin)
        if (! Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
                if (DB::getDriverName() !== 'sqlite') {
                    $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                }
            });

            // Backfill existing users (assign all non-super-admins or existing staff to tenant 1)
            DB::table('users')->whereNull('tenant_id')->update(['tenant_id' => 1]);
        }

        // 2. Add tenant_id column to all business entity tables and backfill
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
                });

                // Backfill existing live data to Default Tenant 1
                DB::table($tableName)->whereNull('tenant_id')->update(['tenant_id' => 1]);

                // Add foreign key constraint (MySQL/PostgreSQL)
                if (DB::getDriverName() !== 'sqlite') {
                    Schema::table($tableName, function (Blueprint $table) {
                        $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                    });
                }
            }
        }

        // 3. Refactor global unique constraints to composite (tenant_id, column) constraints
        $compositeUniques = [
            'categories' => ['slug'],
            'brands' => ['slug'],
            'units' => ['code'],
            'products' => ['code', 'sku'],
            'tables' => ['table_number'],
            'delivery_areas' => ['code'],
            'delivery_riders' => ['employee_id'],
            'accounts' => ['code'],
            'journal_entries' => ['entry_number'],
            'orders' => ['order_number'],
            'purchases' => ['purchase_number'],
            'sale_returns' => ['return_number'],
            'stock_adjustments' => ['adjustment_number'],
            'deals' => ['code'],
            'system_settings' => ['key'],
            'day_closes' => ['business_date'],
        ];

        foreach ($compositeUniques as $tableName => $columns) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($columns) {
                    foreach ($columns as $col) {
                        if (DB::getDriverName() !== 'sqlite') {
                            try {
                                $table->dropUnique([$col]);
                            } catch (Throwable $e) {
                                // Index might not exist or was already replaced
                            }
                        }
                        $table->unique(['tenant_id', $col]);
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Revert unique constraints
            Schema::table('day_closes', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'business_date']);
                $table->unique('business_date');
            });

            Schema::table('system_settings', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'key']);
                $table->unique('key');
            });

            Schema::table('deals', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'code']);
                $table->unique('code');
            });

            Schema::table('stock_adjustments', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'adjustment_number']);
                $table->unique('adjustment_number');
            });

            Schema::table('sale_returns', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'return_number']);
                $table->unique('return_number');
            });

            Schema::table('purchases', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'purchase_number']);
                $table->unique('purchase_number');
            });

            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'order_number']);
                $table->unique('order_number');
            });

            Schema::table('journal_entries', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'entry_number']);
                $table->unique('entry_number');
            });

            Schema::table('accounts', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'code']);
                $table->unique('code');
            });

            Schema::table('delivery_riders', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'employee_id']);
                $table->unique('employee_id');
            });

            Schema::table('delivery_areas', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'code']);
                $table->unique('code');
            });

            Schema::table('tables', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'table_number']);
                $table->unique('table_number');
            });

            Schema::table('products', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'code']);
                $table->dropUnique(['tenant_id', 'sku']);
                $table->unique('code');
                $table->unique('sku');
            });

            Schema::table('units', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'code']);
                $table->unique('code');
            });

            Schema::table('brands', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'slug']);
                $table->unique('slug');
            });

            Schema::table('categories', function (Blueprint $table) {
                $table->dropUnique(['tenant_id', 'slug']);
                $table->unique('slug');
            });
        }

        // Drop foreign keys and columns
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign([$tableName.'_tenant_id_foreign']);
                    $table->dropColumn('tenant_id');
                });
            }
        }

        if (Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['users_tenant_id_foreign']);
                $table->dropColumn('tenant_id');
            });
        }
    }
};
