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
        // 1. Add item type to products table
        // Types:
        // - 'menu_item': Finished food/drink prepared and sold on POS. Has recipe. Hidden from purchases.
        // - 'raw_material': Raw ingredients/supplies (dough, cheese, meat, packaging). Purchased on PO, hidden from POS menu.
        // - 'standard': Direct retail items both purchased and sold as-is (e.g. 1.5L Coke bottle).
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 30)->default('menu_item')->after('barcode')->index();
        });

        // 2. Recipe Items (Bill of Materials / BOM)
        // Defines the raw ingredients consumed to make one unit of a finished menu item
        Schema::create('recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4)->default(1.0000);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'ingredient_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_items');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
