<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('client_uuid', 36)->nullable()->unique()->after('order_number');
            $table->string('offline_order_number', 50)->nullable()->after('client_uuid');
            $table->boolean('is_offline')->default(false)->after('order_status');
            $table->timestamp('synced_at')->nullable()->after('finalized_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['client_uuid', 'offline_order_number', 'is_offline', 'synced_at']);
        });
    }
};
