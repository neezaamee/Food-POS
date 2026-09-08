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
        // 1. Central Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->string('order_type', 30); // TAKEAWAY, DINE_IN, DELIVERY
            $table->string('order_status', 30)->default('draft');
            // draft, confirmed, preparing, ready, assigned, out_for_delivery, delivered, pending_payment, completed, cancelled
            $table->string('payment_status', 30)->default('unpaid'); // unpaid, partially_paid, paid

            // Customer Info
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('customer_name', 150)->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->text('customer_address')->nullable();

            // Dine-In Specific Info
            $table->foreignId('table_id')->nullable()->constrained('tables')->nullOnDelete();
            $table->string('table_name', 100)->nullable();

            // Delivery Specific Info
            $table->foreignId('delivery_area_id')->nullable()->constrained('delivery_areas')->nullOnDelete();
            $table->foreignId('delivery_rider_id')->nullable()->constrained('delivery_riders')->nullOnDelete();
            $table->decimal('delivery_charge', 15, 2)->default(0.00);
            $table->decimal('delivery_distance_km', 8, 2)->default(0.00);
            $table->decimal('rider_starting_km', 10, 2)->nullable();
            $table->decimal('rider_ending_km', 10, 2)->nullable();
            $table->decimal('rider_total_km', 10, 2)->nullable();

            // Financials (Strict Decimal 15,2)
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->string('discount_type', 20)->default('fixed'); // fixed, percent
            $table->decimal('discount_rate', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->decimal('balance_amount', 15, 2)->default(0.00);

            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->string('fbr_invoice_number', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_type', 'order_status']);
            $table->index('finalized_at');
        });

        // 2. Order Items Table
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name', 150);
            $table->string('product_sku', 50)->nullable();
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->string('notes', 255)->nullable(); // e.g. "No Mayo", "Extra Cheese"
            $table->string('status', 30)->default('pending'); // pending, cooking, ready, served, cancelled
            $table->timestamps();

            $table->index(['order_id', 'product_id']);
        });

        // 3. Order Payments (Supports Split & Partial Payments)
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('payment_method', 30); // cash, card, bank, digital, credit
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('payment_reference', 100)->nullable();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_id', 'payment_method']);
        });

        // 4. Stock Movements (Immutable Inventory Ledger)
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('movement_type', 30); // opening, purchase, sale, sale_return, adjustment
            $table->decimal('quantity', 12, 2); // positive for IN, negative for OUT
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('balance_after', 12, 2)->default(0.00);
            $table->string('notes', 255)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'movement_type']);
            $table->index(['reference_type', 'reference_id']);
        });

        // 5. Stock Adjustments
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number', 50)->unique();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('type', 20); // addition, subtraction
            $table->decimal('quantity', 12, 2);
            $table->string('reason', 255);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 6. Purchases (Procurement)
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_number', 50)->unique();
            $table->string('supplier_name', 150);
            $table->date('purchase_date');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->decimal('paid_amount', 15, 2)->default(0.00);
            $table->string('status', 30)->default('received'); // received, pending
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(1.00);
            $table->decimal('unit_cost', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // 7. Sale Returns / Credit Notes
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->string('refund_method', 30)->default('cash'); // cash, credit, bank
            $table->string('reason', 255);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(1.00);
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->timestamps();
        });

        // 8. Cashier Shifts
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('opened_at')->useCurrent();
            $table->decimal('opening_cash', 15, 2)->default(0.00);
            $table->decimal('cash_sales', 15, 2)->default(0.00);
            $table->decimal('cash_receipts', 15, 2)->default(0.00);
            $table->decimal('cash_payments', 15, 2)->default(0.00);
            $table->decimal('refunds', 15, 2)->default(0.00);
            $table->decimal('expected_cash', 15, 2)->default(0.00);
            $table->decimal('actual_cash', 15, 2)->nullable();
            $table->decimal('difference', 15, 2)->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('status', 20)->default('open'); // open, closed
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_shift_id')->constrained('cash_shifts')->cascadeOnDelete();
            $table->string('type', 30); // sale, refund, cash_in, cash_out
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->string('description', 255);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // 9. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('module', 50);
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
        });

        // 10. System Settings & FBR
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });

        Schema::create('fbr_settings', function (Blueprint $table) {
            $table->id();
            $table->string('pos_id', 100)->nullable();
            $table->text('bearer_token')->nullable();
            $table->string('api_url', 255)->nullable();
            $table->string('mode', 30)->default('sandbox'); // sandbox, production
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
        });

        Schema::create('fbr_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('invoice_number', 50);
            $table->string('fbr_invoice_number', 100)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status', 30)->default('pending'); // pending, submitted, failed
            $table->text('error_message')->nullable();
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fbr_submissions');
        Schema::dropIfExists('fbr_settings');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('order_payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
