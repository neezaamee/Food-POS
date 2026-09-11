<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Category;
use App\Models\Plan;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\TableSection;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\SaaS\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantRegistrationController extends Controller
{
    /**
     * Display the tenant registration page
     */
    public function showRegistrationForm()
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();

        return view('saas.register', compact('plans'));
    }

    /**
     * Process tenant registration and auto-seed initial configuration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'restaurant_name' => 'required|string|max:100',
            'slug' => 'required|string|alpha_dash|max:50|unique:tenants,slug',
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => 'required|string|min:6|confirmed',
            'plan_id' => 'required|exists:plans,id',
            'currency' => 'nullable|string|max:10',
        ]);

        $currency = $validated['currency'] ?: 'PKR';
        $currencySymbol = ($currency === 'PKR') ? 'Rs' : '$';

        $plan = Plan::findOrFail($validated['plan_id']);

        $user = DB::transaction(function () use ($validated, $plan, $currency, $currencySymbol) {
            // 1. Create Tenant
            $tenant = Tenant::create([
                'name' => $validated['restaurant_name'],
                'slug' => Str::slug($validated['slug']),
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'currency' => $currency,
                'timezone' => 'Asia/Karachi',
                'status' => 'trial',
            ]);

            // Set context for auto-tenant assignment
            TenantContext::set($tenant);

            // 2. Create Trial Subscription (14 days)
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => 'trial',
                'starts_at' => now(),
                'ends_at' => now()->addDays(14),
                'trial_ends_at' => now()->addDays(14),
            ]);

            // 3. Create Tenant Admin User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
                'status' => 'active',
            ]);

            // Assign Admin Role
            $adminRole = Role::where('slug', 'admin')->first();
            if ($adminRole) {
                $user->roles()->attach($adminRole->id);
            }

            // 4. Seed Default Settings
            SystemSetting::set('restaurant_name', $validated['restaurant_name'], 'general');
            SystemSetting::set('currency', $currencySymbol, 'general');
            SystemSetting::set('currency_symbol', $currencySymbol, 'general');
            SystemSetting::set('tax_rate', '0', 'general');
            SystemSetting::set('business_phone', $validated['phone'] ?? '', 'general');

            // 5. Seed Default Unit & Category
            $unit = Unit::firstOrCreate([
                'tenant_id' => $tenant->id,
                'code' => 'PCS',
            ], [
                'name' => 'Piece',
            ]);

            $category = Category::create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Menu',
                'slug' => 'main-menu-'.$tenant->id,
                'is_active' => true,
            ]);

            // 6. Seed Default Table Section & Tables
            $section = TableSection::create([
                'tenant_id' => $tenant->id,
                'name' => 'Main Dining',
            ]);

            RestaurantTable::create([
                'tenant_id' => $tenant->id,
                'table_number' => 'T-01',
                'name' => 'Table 1',
                'capacity' => 4,
                'status' => 'available',
                'section_id' => $section->id,
            ]);

            RestaurantTable::create([
                'tenant_id' => $tenant->id,
                'table_number' => 'T-02',
                'name' => 'Table 2',
                'capacity' => 4,
                'status' => 'available',
                'section_id' => $section->id,
            ]);

            // 7. Seed Starter Chart of Accounts
            $starterAccounts = [
                ['code' => '1001', 'name' => 'Cash in Hand', 'type' => 'asset', 'nature' => 'debit'],
                ['code' => '1002', 'name' => 'Petty Cash', 'type' => 'asset', 'nature' => 'debit'],
                ['code' => '1003', 'name' => 'Accounts Receivable', 'type' => 'asset', 'nature' => 'debit'],
                ['code' => '1004', 'name' => 'Food Inventory', 'type' => 'asset', 'nature' => 'debit'],
                ['code' => '2001', 'name' => 'Accounts Payable', 'type' => 'liability', 'nature' => 'credit'],
                ['code' => '4001', 'name' => 'Food Sales Revenue', 'type' => 'income', 'nature' => 'credit'],
                ['code' => '5001', 'name' => 'Cost of Goods Sold (COGS)', 'type' => 'expense', 'nature' => 'debit'],
                ['code' => '5002', 'name' => 'Kitchen Operating Expenses', 'type' => 'expense', 'nature' => 'debit'],
            ];

            foreach ($starterAccounts as $acc) {
                Account::create([
                    'tenant_id' => $tenant->id,
                    'code' => $acc['code'],
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'level' => 1,
                    'debit_credit_nature' => $acc['nature'],
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'is_system' => true,
                    'is_active' => true,
                ]);
            }

            return $user;
        });

        // Log the user in directly
        Auth::login($user);

        return redirect()->route('pos.index')->with('success', "Welcome to {$validated['restaurant_name']}! Your 14-day trial has been activated.");
    }
}
