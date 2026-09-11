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
        Schema::create('day_closes', function (Blueprint $table) {
            $table->id();
            $table->date('business_date')->index();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_shifts_count')->default(0);
            $table->unsignedInteger('total_orders_count')->default(0);
            $table->decimal('gross_sales', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('delivery_charges', 15, 2)->default(0.00);
            $table->decimal('net_sales', 15, 2)->default(0.00);
            $table->decimal('cash_sales', 15, 2)->default(0.00);
            $table->decimal('digital_sales', 15, 2)->default(0.00);
            $table->decimal('credit_sales', 15, 2)->default(0.00);
            $table->decimal('total_refunds', 15, 2)->default(0.00);
            $table->decimal('opening_cash_total', 15, 2)->default(0.00);
            $table->decimal('expected_cash_total', 15, 2)->default(0.00);
            $table->decimal('actual_cash_total', 15, 2)->default(0.00);
            $table->decimal('difference_total', 15, 2)->default(0.00);
            $table->json('shift_ids')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('day_closes');
    }
};
