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
        if (! Schema::hasTable('payment_gateway_transactions')) {
            Schema::create('payment_gateway_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->string('order_number', 60)->nullable()->index();
                $table->string('provider', 30)->index(); // jazzcash, easypaisa, nayapay, raast, simulator
                $table->string('channel', 30)->default('push_request'); // push_request, dynamic_qr, checkout_url
                $table->string('transaction_reference', 100)->unique();
                $table->string('mobile_number', 30)->nullable();
                $table->decimal('amount', 15, 2);
                $table->decimal('fee_amount', 15, 2)->default(0.00);
                $table->string('currency', 10)->default('PKR');
                $table->string('status', 30)->default('initiated')->index(); // initiated, pending_customer, completed, failed, expired, cancelled
                $table->string('environment', 20)->default('sandbox'); // sandbox, production
                $table->string('gateway_response_code', 50)->nullable();
                $table->text('gateway_response_message')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                if (DB::getDriverName() !== 'sqlite') {
                    $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                }

                $table->index(['tenant_id', 'provider', 'status']);
                $table->index(['order_id', 'provider']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
