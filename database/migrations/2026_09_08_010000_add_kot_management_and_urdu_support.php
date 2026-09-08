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
        // 1. Add KOT Status to orders table if not already present
        if (! Schema::hasColumn('orders', 'kot_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('kot_status', 30)->default('prep')->after('order_status');
                $table->index('kot_status');
            });
        }

        // 2. Add Urdu Name to products table
        if (! Schema::hasColumn('products', 'name_ur')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('name_ur', 150)->nullable()->after('name');
            });
        }

        // 3. Add Urdu Name to order_items table
        if (! Schema::hasColumn('order_items', 'product_name_ur')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('product_name_ur', 150)->nullable()->after('product_name');
            });
        }

        // 4. Create KOTs table for tracking incremental KOT batches per order
        if (! Schema::hasTable('kots')) {
            Schema::create('kots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->unsignedInteger('kot_number')->default(1);
                $table->string('status', 30)->default('prep'); // prep, marination, baking, packing, ready
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'kot_number']);
            });
        }

        // 5. Create KOT Items table for tracking specific items sent in each KOT batch
        if (! Schema::hasTable('kot_items')) {
            Schema::create('kot_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('kot_id')->constrained('kots')->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('product_name', 150);
                $table->string('product_name_ur', 150)->nullable();
                $table->decimal('quantity', 10, 2)->default(1.00);
                $table->string('notes', 255)->nullable();
                $table->string('status', 30)->default('prep');
                $table->timestamps();

                $table->index(['kot_id', 'product_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kot_items');
        Schema::dropIfExists('kots');

        if (Schema::hasColumn('order_items', 'product_name_ur')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('product_name_ur');
            });
        }

        if (Schema::hasColumn('products', 'name_ur')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('name_ur');
            });
        }

        if (Schema::hasColumn('orders', 'kot_status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['kot_status']);
                $table->dropColumn('kot_status');
            });
        }
    }
};
