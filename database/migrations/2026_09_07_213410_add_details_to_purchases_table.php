<?php

use App\Models\Unit;
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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('invoice_number', 100)->nullable()->after('supplier_name');
            $table->string('payment_method', 30)->default('cash')->after('paid_amount');
            $table->text('notes')->nullable()->after('status');
        });

        // Seed common standard Food & Restaurant Units of Measure
        $commonUnits = [
            ['code' => 'LTR', 'name' => 'Liter'],
            ['code' => 'GRAM', 'name' => 'Gram / G'],
            ['code' => 'PACK', 'name' => 'Packet / Pack'],
            ['code' => 'BOX', 'name' => 'Box / Carton'],
            ['code' => 'DOZEN', 'name' => 'Dozen'],
            ['code' => 'CAN', 'name' => 'Can / Tin'],
        ];

        foreach ($commonUnits as $u) {
            Unit::firstOrCreate(['code' => $u['code']], ['name' => $u['name']]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'payment_method', 'notes']);
        });
    }
};
