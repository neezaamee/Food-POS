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
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id')->index();
                    if (DB::getDriverName() !== 'sqlite') {
                        $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                    }
                }

                if (! Schema::hasColumn('roles', 'is_system')) {
                    $table->boolean('is_system')->default(false)->after('description')->index();
                }
            });

            // Mark all existing pre-seeded roles as system roles
            DB::table('roles')->whereNull('tenant_id')->update(['is_system' => true]);

            // Handle composite uniqueness if not sqlite
            if (DB::getDriverName() !== 'sqlite') {
                try {
                    Schema::table('roles', function (Blueprint $table) {
                        $table->dropUnique(['slug']);
                        $table->unique(['tenant_id', 'slug']);
                    });
                } catch (Throwable $e) {
                    // Unique constraint might already be adjusted
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (DB::getDriverName() !== 'sqlite') {
                    try {
                        $table->dropUnique(['tenant_id', 'slug']);
                        $table->unique('slug');
                    } catch (Throwable $e) {
                    }
                }

                if (Schema::hasColumn('roles', 'tenant_id')) {
                    if (DB::getDriverName() !== 'sqlite') {
                        $table->dropForeign(['tenant_id']);
                    }
                    $table->dropColumn('tenant_id');
                }

                if (Schema::hasColumn('roles', 'is_system')) {
                    $table->dropColumn('is_system');
                }
            });
        }
    }
};
