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
        // 1. Tenants Table
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 100)->unique();
            $table->string('phone', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('currency', 20)->default('Rs.');
            $table->string('timezone', 50)->default('Asia/Karachi');
            $table->string('status', 30)->default('active'); // active, trial, past_due, suspended, cancelled
            $table->dateTime('trial_ends_at')->nullable();
            $table->string('logo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 2. Plans Table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2)->default(0.00);
            $table->decimal('price_yearly', 10, 2)->default(0.00);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->unsignedInteger('max_users')->default(3); // 0 = unlimited
            $table->unsignedInteger('max_products')->default(200); // 0 = unlimited
            $table->unsignedInteger('max_monthly_orders')->default(1000); // 0 = unlimited
            $table->unsignedInteger('max_tables')->default(20); // 0 = unlimited
            $table->json('features')->nullable();
            $table->timestamps();
        });

        // 3. Subscriptions Table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('status', 30)->default('active'); // active, trial, past_due, expired, cancelled, suspended
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly, lifetime
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('grace_ends_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // 4. Subscription Invoices Table
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('payment_method', 50)->default('manual');
            $table->string('payment_reference', 100)->nullable();
            $table->string('status', 30)->default('pending'); // paid, pending, failed, rejected
            $table->text('notes')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // 5. Seed Default Tenant #1 & Standard SaaS Plans
        $defaultTenantId = DB::table('tenants')->insertGetId([
            'name' => 'Food Point (Main Restaurant)',
            'slug' => 'food-point-main',
            'phone' => '+92 42 111-366-376',
            'email' => 'admin@foodpoint.com',
            'address' => 'Plot 14-B, Commercial Zone, Phase 5 DHA, Lahore',
            'currency' => 'Rs.',
            'timezone' => 'Asia/Karachi',
            'status' => 'active',
            'trial_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $starterPlanId = DB::table('plans')->insertGetId([
            'name' => 'Starter Cafe',
            'slug' => 'starter',
            'description' => 'Ideal for single food points, cafes and small kiosks with essential POS operations.',
            'price_monthly' => 3500.00,
            'price_yearly' => 35000.00,
            'is_active' => true,
            'is_popular' => false,
            'max_users' => 3,
            'max_products' => 150,
            'max_monthly_orders' => 1000,
            'max_tables' => 15,
            'features' => json_encode(['pos', 'tables', 'takeaway', 'delivery', 'receipts']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $proPlanId = DB::table('plans')->insertGetId([
            'name' => 'Professional Dining',
            'slug' => 'professional',
            'description' => 'Full-featured solution with Kitchen Display, Inventory control, Recipes, and WhatsApp receipts.',
            'price_monthly' => 7500.00,
            'price_yearly' => 75000.00,
            'is_active' => true,
            'is_popular' => true,
            'max_users' => 10,
            'max_products' => 600,
            'max_monthly_orders' => 5000,
            'max_tables' => 50,
            'features' => json_encode(['pos', 'tables', 'takeaway', 'delivery', 'receipts', 'kitchen', 'inventory', 'recipes', 'deals', 'whatsapp', 'shifts']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $enterprisePlanId = DB::table('plans')->insertGetId([
            'name' => 'Enterprise Multi-Branch',
            'slug' => 'enterprise',
            'description' => 'Unlimited capacity with Double-Entry Accounting, FBR digital invoicing, and priority support.',
            'price_monthly' => 15000.00,
            'price_yearly' => 150000.00,
            'is_active' => true,
            'is_popular' => false,
            'max_users' => 0, // 0 = unlimited
            'max_products' => 0,
            'max_monthly_orders' => 0,
            'max_tables' => 0,
            'features' => json_encode(['pos', 'tables', 'takeaway', 'delivery', 'receipts', 'kitchen', 'inventory', 'recipes', 'deals', 'whatsapp', 'shifts', 'accounting', 'fbr']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign Default Tenant to the Enterprise plan with Lifetime Active status
        DB::table('subscriptions')->insert([
            'tenant_id' => $defaultTenantId,
            'plan_id' => $enterprisePlanId,
            'status' => 'active',
            'billing_cycle' => 'lifetime',
            'starts_at' => now(),
            'ends_at' => now()->addYears(10),
            'trial_ends_at' => null,
            'grace_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('tenants');
    }
};
