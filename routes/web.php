<?php

use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\WhatsAppController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\PosOfflineController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayCloseController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaaS\TenantRegistrationController;
use App\Http\Controllers\SuperAdmin\SaasAdminController;
use App\Livewire\Pos\PosScreen;
use Illuminate\Support\Facades\Route;

// Authentication & Public SaaS Onboarding Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'loginPost'])->name('login.post');

    Route::get('/register-tenant', [TenantRegistrationController::class, 'showRegistrationForm'])->name('tenant.register');
    Route::post('/register-tenant', [TenantRegistrationController::class, 'register'])->name('tenant.register.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Protected Application Routes
Route::middleware('auth')->group(function () {

    // Root Redirect to Dashboard
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Executive Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Central POS Engine (Livewire Single-Page Terminal)
    Route::get('/pos/{orderId?}', PosScreen::class)->name('pos.index')->middleware(['permission:pos.access']);
    Route::get('/restaurant/pos', function () {
        return redirect()->route('pos.index');
    })->middleware(['permission:pos.access']);

    // POS Offline Support API (IndexedDB Catalog & Sync)
    Route::prefix('pos/api')->name('pos.api.')->middleware(['permission:pos.access'])->group(function () {
        Route::get('/ping', fn () => response()->json(['ok' => true, 'timestamp' => now()->timestamp]))->name('ping');
        Route::get('/catalog', [PosOfflineController::class, 'getCatalog'])->name('catalog');
        Route::post('/sync', [PosOfflineController::class, 'syncOrders'])->name('sync');
    });

    // Orders & Sales Returns
    Route::prefix('orders')->name('orders.')->middleware(['permission:orders.view|pos.access'])->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::get('/{id}/thermal', [OrderController::class, 'thermal'])->name('thermal');
        Route::post('/{id}/whatsapp', [OrderController::class, 'sendWhatsApp'])->name('whatsapp');
        Route::post('/{id}/return', [OrderController::class, 'processReturn'])->name('return.process');
        Route::post('/{id}/cancel', [OrderController::class, 'cancelOrder'])->name('cancel');
    });
    Route::get('/returns', [OrderController::class, 'returnsIndex'])->name('orders.returns.index')->middleware(['permission:orders.returns|orders.view']);

    // Restaurant Operations (Floor, Kitchen, Delivery)
    Route::prefix('restaurant')->name('restaurant.')->group(function () {
        Route::middleware(['permission:tables.manage'])->group(function () {
            Route::get('/tables', [RestaurantController::class, 'tables'])->name('tables');
            Route::post('/sections', [RestaurantController::class, 'storeSection'])->name('sections.store');
            Route::post('/tables', [RestaurantController::class, 'storeTable'])->name('tables.store');
        });

        Route::middleware(['permission:kitchen.view'])->group(function () {
            Route::get('/kitchen', [RestaurantController::class, 'kitchen'])->name('kitchen');
            Route::post('/kitchen/order/{id}/status', [RestaurantController::class, 'updateOrderStatus'])->name('kitchen.order.status');
            Route::post('/kitchen/item/{id}/status', [RestaurantController::class, 'updateItemStatus'])->name('kitchen.status');
            Route::get('/kot/{id}/print', [RestaurantController::class, 'kotPrint'])->name('kot.print');
        });

        Route::middleware(['permission:delivery.manage'])->group(function () {
            Route::get('/delivery', [RestaurantController::class, 'deliveryAreas'])->name('delivery');
            Route::post('/delivery', [RestaurantController::class, 'storeDeliveryArea'])->name('delivery.store');
            Route::get('/riders', [RestaurantController::class, 'riders'])->name('riders');
            Route::post('/riders', [RestaurantController::class, 'storeRider'])->name('riders.store');
            Route::post('/delivery/order/{id}/mileage', [RestaurantController::class, 'updateOrderMileage'])->name('delivery.order.mileage');
        });
    });

    // Catalog & Master Resources
    Route::prefix('resources')->name('resources.')->group(function () {
        // Products & Recipes
        Route::middleware(['permission:products.manage'])->group(function () {
            Route::get('/products', [ProductController::class, 'index'])->name('products.index');
            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
            Route::put('/products/{id}', [ProductController::class, 'update'])->name('products.update');
            Route::get('/products/{id}/recipe', [ProductController::class, 'getRecipe'])->name('products.recipe');
            Route::get('/products/{id}/edit-data', [ProductController::class, 'getEditData'])->name('products.edit-data');
            Route::post('/products/{id}/recipe', [ProductController::class, 'saveRecipe'])->name('products.recipe.save');
            Route::post('/products/{id}/variants', [ProductController::class, 'manageVariants'])->name('products.variants.save');
            Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
        });

        // Packages & Deals
        Route::middleware(['permission:deals.manage|products.manage'])->group(function () {
            Route::get('/deals', [DealController::class, 'index'])->name('deals.index');
            Route::post('/deals', [DealController::class, 'store'])->name('deals.store');
            Route::put('/deals/{id}', [DealController::class, 'update'])->name('deals.update');
            Route::delete('/deals/{id}', [DealController::class, 'destroy'])->name('deals.destroy');
            Route::post('/deals/{id}/toggle-status', [DealController::class, 'toggleStatus'])->name('deals.toggle-status');
        });

        // Categories & Brands & Units
        Route::middleware(['permission:categories.manage|products.manage'])->group(function () {
            Route::get('/categories', [ResourceController::class, 'categories'])->name('categories.index');
            Route::post('/categories', [ResourceController::class, 'storeCategory'])->name('categories.store');
            Route::get('/brands', [ResourceController::class, 'brands'])->name('brands.index');
            Route::post('/brands', [ResourceController::class, 'storeBrand'])->name('brands.store');
            Route::get('/units', [ResourceController::class, 'units'])->name('units.index');
            Route::post('/units', [ResourceController::class, 'storeUnit'])->name('units.store');
        });

        // Customers
        Route::middleware(['permission:customers.manage'])->group(function () {
            Route::get('/customers', [ResourceController::class, 'customers'])->name('customers.index');
            Route::post('/customers', [ResourceController::class, 'storeCustomer'])->name('customers.store');
        });
    });

    // Inventory & Purchasing
    Route::prefix('inventory')->name('inventory.')->middleware(['feature:inventory.management', 'permission:inventory.manage'])->group(function () {
        Route::get('/overview', [InventoryController::class, 'overview'])->name('overview');
        Route::get('/ledger', [InventoryController::class, 'ledger'])->name('ledger');
        Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('adjustments');
        Route::get('/purchases', [InventoryController::class, 'purchases'])->name('purchases');
        Route::post('/purchases', [InventoryController::class, 'storePurchase'])->name('purchases.store');
        Route::get('/purchases/{id}', [InventoryController::class, 'showPurchase'])->name('purchases.show');
        Route::get('/purchases/{id}/edit-data', [InventoryController::class, 'editPurchaseData'])->name('purchases.edit-data');
        Route::put('/purchases/{id}', [InventoryController::class, 'updatePurchase'])->name('purchases.update');
        Route::delete('/purchases/{id}', [InventoryController::class, 'destroyPurchase'])->name('purchases.destroy');
        Route::post('/purchases/products', [InventoryController::class, 'storePurchaseProduct'])->name('purchases.products.store');
    });

    // Finance & Double-Entry Accounting
    Route::prefix('finance')->name('finance.')->middleware(['feature:accounting.ledger', 'permission:accounting.access'])->group(function () {
        Route::get('/chart-of-accounts', [FinanceController::class, 'chartOfAccounts'])->name('chart-of-accounts');
        Route::post('/chart-of-accounts', [FinanceController::class, 'storeAccount'])->name('chart-of-accounts.store');
        Route::get('/receipts', [FinanceController::class, 'receipts'])->name('receipts');
        Route::post('/receipts', [FinanceController::class, 'storeReceipt'])->name('receipts.store');
        Route::get('/payments', [FinanceController::class, 'payments'])->name('payments');
        Route::post('/payments', [FinanceController::class, 'storePayment'])->name('payments.store');
        Route::get('/vouchers', [FinanceController::class, 'vouchers'])->name('vouchers');
        Route::post('/vouchers', [FinanceController::class, 'storeVoucher'])->name('vouchers.store');
        Route::get('/ledger', [FinanceController::class, 'ledger'])->name('ledger');
        Route::get('/trial-balance', [FinanceController::class, 'trialBalance'])->name('trial-balance');
    });

    // Cash Shifts & Drawer Reconciliation
    Route::prefix('cash')->name('cash.')->group(function () {
        Route::get('/shifts', [CashShiftController::class, 'index'])->name('shifts')->middleware(['permission:cash.shifts']);
        Route::post('/shifts/open', [CashShiftController::class, 'open'])->name('shifts.open')->middleware(['permission:cash.shifts']);
        Route::post('/shifts/{id}/close', [CashShiftController::class, 'close'])->name('shifts.close')->middleware(['permission:cash.shift-close']);
        Route::post('/shifts/{id}/transaction', [CashShiftController::class, 'transaction'])->name('shifts.transaction')->middleware(['permission:cash.shifts']);

        // Day Close & Z-Report
        Route::middleware(['permission:cash.day-close'])->group(function () {
            Route::get('/day-close', [DayCloseController::class, 'index'])->name('day-close.index');
            Route::post('/day-close', [DayCloseController::class, 'closeDay'])->name('day-close.store');
            Route::get('/day-close/{id}/z-report', [DayCloseController::class, 'zReport'])->name('day-close.z-report');
        });
    });

    // Business & Financial Analytics Reports
    Route::prefix('reports')->name('reports.')->middleware(['feature:reports.advanced', 'permission:reports.view'])->group(function () {
        Route::get('/daily-sales', [ReportController::class, 'dailySales'])->name('daily-sales');
        Route::get('/shift-sales', [ReportController::class, 'shiftSales'])->name('shift-sales');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/delivery', [ReportController::class, 'delivery'])->name('delivery');
        Route::get('/tables', [ReportController::class, 'tables'])->name('tables');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/payments', [ReportController::class, 'payments'])->name('payments');
        Route::get('/customer-ledger', [ReportController::class, 'customerLedger'])->name('customer-ledger');
        Route::post('/customer-ledger/share-whatsapp', [ReportController::class, 'shareCustomerBalanceWhatsApp'])->name('customer-ledger.share-whatsapp');
    });

    // Administration, Settings, Profile & Auditing
    Route::prefix('admin')->group(function () {
        // Users & Roles
        Route::middleware(['permission:users.manage'])->group(function () {
            Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
            Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
            Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
            Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
        });

        // Roles & Permissions Management
        Route::resource('roles', RoleController::class)->middleware(['permission:roles.manage']);

        // Profile (Available to all logged-in staff)
        Route::get('/profile', [AdminController::class, 'profile'])->name('admin.profile');
        Route::post('/profile', [AdminController::class, 'updateProfile'])->name('admin.profile.update');

        // Store Settings
        Route::middleware(['permission:settings.access'])->group(function () {
            Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
            Route::post('/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
        });

        // FBR Digital Invoicing
        Route::middleware(['feature:compliance.fbr', 'permission:fbr.access'])->group(function () {
            Route::get('/fbr', [AdminController::class, 'fbr'])->name('admin.fbr');
            Route::post('/fbr', [AdminController::class, 'updateFbr'])->name('admin.fbr.update');
        });

        // WhatsApp Integration
        Route::prefix('whatsapp')->name('admin.whatsapp.')->middleware(['feature:marketing.whatsapp', 'permission:settings.access'])->group(function () {
            Route::get('/', [WhatsAppController::class, 'index'])->name('index');
            Route::get('/status', [WhatsAppController::class, 'status'])->name('status');
            Route::post('/reconnect', [WhatsAppController::class, 'reconnect'])->name('reconnect');
            Route::post('/disconnect', [WhatsAppController::class, 'disconnect'])->name('disconnect');
            Route::post('/test', [WhatsAppController::class, 'test'])->name('test');
            Route::post('/settings', [WhatsAppController::class, 'updateSettings'])->name('settings');
        });

        // Third-Party Digital Payment Gateways (JazzCash, EasyPaisa, NayaPay, Raast QR, Simulator)
        Route::prefix('payments')->name('admin.payments.')->middleware(['permission:settings.access'])->group(function () {
            Route::get('/', [PaymentGatewayController::class, 'index'])->name('index');
            Route::post('/settings', [PaymentGatewayController::class, 'updateSettings'])->name('settings');
            Route::post('/test', [PaymentGatewayController::class, 'testTransaction'])->name('test');
            Route::post('/simulate', [PaymentGatewayController::class, 'simulateAction'])->name('simulate');
        });

        // Audit Trail
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('admin.audit-logs')->middleware(['permission:audit.view']);
    });

    // Route Name Aliases for Menu Navigation Compatibility
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile.index');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings.index')->middleware(['permission:settings.access']);
    Route::get('/users', [AdminController::class, 'users'])->name('users.index')->middleware(['permission:users.manage']);
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index')->middleware(['permission:roles.manage']);
    Route::get('/fbr', [AdminController::class, 'fbr'])->name('fbr.index')->middleware(['permission:fbr.access']);
    Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index')->middleware(['permission:settings.access']);
    Route::get('/payment-gateways', [PaymentGatewayController::class, 'index'])->name('payment-gateways.index')->middleware(['permission:settings.access']);
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs.index')->middleware(['permission:audit.view']);

    // SaaS Platform Super-Admin Management
    Route::middleware(['super_admin'])->prefix('saas-admin')->name('saas.')->group(function () {
        Route::get('/', [SaasAdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/tenants', [SaasAdminController::class, 'tenants'])->name('tenants.index');
        Route::post('/tenants', [SaasAdminController::class, 'storeTenant'])->name('tenants.store');
        Route::get('/tenants/{tenant}', [SaasAdminController::class, 'tenantDetails'])->name('tenants.show');
        Route::patch('/tenants/{tenant}/status', [SaasAdminController::class, 'updateTenantStatus'])->name('tenants.status');
        Route::post('/tenants/{tenant}/features', [SaasAdminController::class, 'updateTenantFeatures'])->name('tenants.features');
        Route::patch('/tenants/{tenant}/plan', [SaasAdminController::class, 'updateTenantPlan'])->name('tenants.plan');
        Route::get('/tenants/{tenant}/impersonate', [SaasAdminController::class, 'impersonate'])->name('tenants.impersonate');
        Route::get('/exit-impersonation', [SaasAdminController::class, 'exitImpersonation'])->name('exit-impersonation');

        // Owners Directory
        Route::get('/owners', [SaasAdminController::class, 'owners'])->name('owners.index');
        Route::post('/owners', [SaasAdminController::class, 'storeOwner'])->name('owners.store');

        // Plans & Features
        Route::get('/plans', [SaasAdminController::class, 'plans'])->name('plans.index');
        Route::post('/plans', [SaasAdminController::class, 'storePlan'])->name('plans.store');
        Route::put('/plans/{plan}', [SaasAdminController::class, 'updatePlan'])->name('plans.update');
        Route::post('/plans/{plan}/toggle', [SaasAdminController::class, 'togglePlanStatus'])->name('plans.toggle');
    });
});
