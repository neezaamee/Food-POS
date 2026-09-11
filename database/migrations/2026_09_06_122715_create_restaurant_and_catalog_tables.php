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
        // 1. Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->index();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Brands
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Units
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('code', 20)->index();
            $table->timestamps();
        });

        // 4. Products
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('code', 50)->nullable()->index();
            $table->string('sku', 50)->nullable()->index();
            $table->string('barcode', 100)->nullable()->index();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('cost_price', 15, 2)->default(0.00);
            $table->decimal('sale_price', 15, 2)->default(0.00);
            $table->decimal('tax_percent', 5, 2)->default(0.00);
            $table->string('image')->nullable();
            $table->decimal('current_stock', 12, 2)->default(0.00);
            $table->decimal('min_stock', 12, 2)->default(5.00);
            $table->unsignedInteger('prep_time_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('mobile', 30)->index();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('area', 100)->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0.00);
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->decimal('current_balance', 15, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Table Sections
        Schema::create('table_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. Restaurant Tables
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number', 50)->index();
            $table->string('name', 100);
            $table->foreignId('section_id')->constrained('table_sections')->cascadeOnDelete();
            $table->unsignedInteger('capacity')->default(4);
            $table->string('status', 30)->default('available'); // available, occupied, reserved, cleaning, out_of_service
            $table->unsignedBigInteger('active_order_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 8. Delivery Areas
        Schema::create('delivery_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 30)->index();
            $table->decimal('delivery_charge', 15, 2)->default(0.00);
            $table->decimal('estimated_distance_km', 8, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 9. Delivery Riders
        Schema::create('delivery_riders', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('mobile', 30)->index();
            $table->string('employee_id', 50)->index();
            $table->string('vehicle_type', 50)->default('Motorbike');
            $table->string('vehicle_number', 50)->nullable();
            $table->string('status', 30)->default('available'); // available, assigned, on_delivery, offline
            $table->date('joining_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_riders');
        Schema::dropIfExists('delivery_areas');
        Schema::dropIfExists('tables');
        Schema::dropIfExists('table_sections');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('units');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};
