<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryArea;
use App\Models\DeliveryRider;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\StockMovement;
use App\Models\SystemSetting;
use App\Models\TableSection;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

class FoodPointRestaurantSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Chart of Accounts
        $accounts = [
            // Assets
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset', 'level' => 1, 'nature' => 'debit', 'parent' => null],
            ['code' => '1100', 'name' => 'Current Assets', 'type' => 'asset', 'level' => 2, 'nature' => 'debit', 'parent' => '1000'],
            ['code' => '1110', 'name' => 'Cash on Hand', 'type' => 'asset', 'level' => 3, 'nature' => 'debit', 'parent' => '1100', 'system' => true],
            ['code' => '1120', 'name' => 'Main Bank Account', 'type' => 'asset', 'level' => 3, 'nature' => 'debit', 'parent' => '1100', 'system' => true],
            ['code' => '1130', 'name' => 'Accounts Receivable (Customers)', 'type' => 'asset', 'level' => 3, 'nature' => 'debit', 'parent' => '1100', 'system' => true],
            ['code' => '1140', 'name' => 'Inventory Asset', 'type' => 'asset', 'level' => 3, 'nature' => 'debit', 'parent' => '1100', 'system' => true],

            // Liabilities
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability', 'level' => 1, 'nature' => 'credit', 'parent' => null],
            ['code' => '2100', 'name' => 'Current Liabilities', 'type' => 'liability', 'level' => 2, 'nature' => 'credit', 'parent' => '2000'],
            ['code' => '2110', 'name' => 'Sales Tax Payable', 'type' => 'liability', 'level' => 3, 'nature' => 'credit', 'parent' => '2100', 'system' => true],
            ['code' => '2120', 'name' => 'Accounts Payable (Suppliers)', 'type' => 'liability', 'level' => 3, 'nature' => 'credit', 'parent' => '2100', 'system' => true],

            // Equity
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity', 'level' => 1, 'nature' => 'credit', 'parent' => null],
            ['code' => '3100', 'name' => 'Owner Capital', 'type' => 'equity', 'level' => 2, 'nature' => 'credit', 'parent' => '3000', 'system' => true],

            // Income
            ['code' => '4000', 'name' => 'Revenue / Income', 'type' => 'income', 'level' => 1, 'nature' => 'credit', 'parent' => null],
            ['code' => '4100', 'name' => 'Sales Revenue', 'type' => 'income', 'level' => 2, 'nature' => 'credit', 'parent' => '4000'],
            ['code' => '4110', 'name' => 'Food Sales Revenue', 'type' => 'income', 'level' => 3, 'nature' => 'credit', 'parent' => '4100', 'system' => true],
            ['code' => '4120', 'name' => 'Delivery Charges Income', 'type' => 'income', 'level' => 3, 'nature' => 'credit', 'parent' => '4100', 'system' => true],

            // Expenses
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense', 'level' => 1, 'nature' => 'debit', 'parent' => null],
            ['code' => '5100', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'level' => 2, 'nature' => 'debit', 'parent' => '5000', 'system' => true],
            ['code' => '5110', 'name' => 'Food & Material COGS', 'type' => 'expense', 'level' => 3, 'nature' => 'debit', 'parent' => '5100', 'system' => true],
            ['code' => '5200', 'name' => 'Operating Expenses', 'type' => 'expense', 'level' => 2, 'nature' => 'debit', 'parent' => '5000'],
            ['code' => '5210', 'name' => 'Rider Delivery Allowance', 'type' => 'expense', 'level' => 3, 'nature' => 'debit', 'parent' => '5200'],
            ['code' => '5220', 'name' => 'Utilities & Gas Expense', 'type' => 'expense', 'level' => 3, 'nature' => 'debit', 'parent' => '5200'],
        ];

        $accountMap = [];
        foreach ($accounts as $acc) {
            $parentId = isset($acc['parent']) && isset($accountMap[$acc['parent']]) ? $accountMap[$acc['parent']]->id : null;
            $model = Account::firstOrCreate(['code' => $acc['code']], [
                'name' => $acc['name'],
                'type' => $acc['type'],
                'level' => $acc['level'],
                'parent_id' => $parentId,
                'debit_credit_nature' => $acc['nature'],
                'is_system' => $acc['system'] ?? false,
                'is_active' => true,
            ]);
            $accountMap[$acc['code']] = $model;
        }

        // 2. Units
        $unitPcs = Unit::firstOrCreate(['code' => 'PCS'], ['name' => 'Piece / Item']);
        $unitKg = Unit::firstOrCreate(['code' => 'KG'], ['name' => 'Kilogram']);
        $unitPortion = Unit::firstOrCreate(['code' => 'PORTION'], ['name' => 'Portion / Plate']);

        // 3. Brands
        $brandKitchen = Brand::firstOrCreate(['slug' => 'food-point-kitchen'], ['name' => 'Food Point Kitchen', 'is_active' => true]);
        $brandBeverage = Brand::firstOrCreate(['slug' => 'beverages-co'], ['name' => 'Beverage Suppliers', 'is_active' => true]);

        // 4. Categories
        $categoriesData = [
            ['name' => 'Burgers & Sandwiches', 'slug' => 'burgers-sandwiches', 'description' => 'Crispy and smashed gourmet burgers'],
            ['name' => 'Pizza & Calzones', 'slug' => 'pizza-calzones', 'description' => 'Stone-baked thin crust and pan pizzas'],
            ['name' => 'Fried Chicken & Wings', 'slug' => 'fried-chicken-wings', 'description' => 'Golden fried chicken and glazed wings'],
            ['name' => 'BBQ & Karahi', 'slug' => 'bbq-karahi', 'description' => 'Traditional clay oven BBQ and handi'],
            ['name' => 'Beverages & Shakes', 'slug' => 'beverages-shakes', 'description' => 'Chilled sodas, lemonades, and thick shakes'],
            ['name' => 'Desserts & Sweets', 'slug' => 'desserts-sweets', 'description' => 'Molten cakes, sundaes, and ice cream'],
        ];

        $catMap = [];
        foreach ($categoriesData as $c) {
            $catMap[$c['slug']] = Category::firstOrCreate(['slug' => $c['slug']], $c);
        }

        // 5. Products
        $productsData = [
            // Burgers
            [
                'name' => 'Zinger Burger Deluxe',
                'code' => 'FP-ZNG-01',
                'sku' => 'FP-ZNG-01',
                'barcode' => '100001',
                'category_id' => $catMap['burgers-sandwiches']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPcs->id,
                'cost_price' => 240.00,
                'sale_price' => 450.00,
                'tax_percent' => 0.00,
                'current_stock' => 150.00,
                'min_stock' => 20.00,
                'prep_time_minutes' => 10,
            ],
            [
                'name' => 'Beef Double Smash Burger',
                'code' => 'FP-BF-02',
                'sku' => 'FP-BF-02',
                'barcode' => '100002',
                'category_id' => $catMap['burgers-sandwiches']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPcs->id,
                'cost_price' => 420.00,
                'sale_price' => 750.00,
                'tax_percent' => 0.00,
                'current_stock' => 90.00,
                'min_stock' => 15.00,
                'prep_time_minutes' => 12,
            ],
            // Pizza
            [
                'name' => 'Chicken Fajita Pizza (Large)',
                'code' => 'FP-PZ-01',
                'sku' => 'FP-PZ-01',
                'barcode' => '100003',
                'category_id' => $catMap['pizza-calzones']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPcs->id,
                'cost_price' => 680.00,
                'sale_price' => 1350.00,
                'tax_percent' => 0.00,
                'current_stock' => 80.00,
                'min_stock' => 10.00,
                'prep_time_minutes' => 20,
            ],
            [
                'name' => 'Pepperoni Feast Pizza (Large)',
                'code' => 'FP-PZ-02',
                'sku' => 'FP-PZ-02',
                'barcode' => '100004',
                'category_id' => $catMap['pizza-calzones']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPcs->id,
                'cost_price' => 720.00,
                'sale_price' => 1450.00,
                'tax_percent' => 0.00,
                'current_stock' => 65.00,
                'min_stock' => 10.00,
                'prep_time_minutes' => 20,
            ],
            // Fried Chicken
            [
                'name' => 'Crispy Fried Chicken (3 Pcs)',
                'code' => 'FP-FC-01',
                'sku' => 'FP-FC-01',
                'barcode' => '100005',
                'category_id' => $catMap['fried-chicken-wings']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPortion->id,
                'cost_price' => 300.00,
                'sale_price' => 550.00,
                'tax_percent' => 0.00,
                'current_stock' => 120.00,
                'min_stock' => 25.00,
                'prep_time_minutes' => 15,
            ],
            [
                'name' => 'Spicy Hot Wings (8 Pcs)',
                'code' => 'FP-WG-01',
                'sku' => 'FP-WG-01',
                'barcode' => '100006',
                'category_id' => $catMap['fried-chicken-wings']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPortion->id,
                'cost_price' => 260.00,
                'sale_price' => 480.00,
                'tax_percent' => 0.00,
                'current_stock' => 100.00,
                'min_stock' => 20.00,
                'prep_time_minutes' => 12,
            ],
            // Karahi & BBQ
            [
                'name' => 'Chicken White Karahi (Full)',
                'code' => 'FP-KR-01',
                'sku' => 'FP-KR-01',
                'barcode' => '100007',
                'category_id' => $catMap['bbq-karahi']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPortion->id,
                'cost_price' => 950.00,
                'sale_price' => 1850.00,
                'tax_percent' => 0.00,
                'current_stock' => 40.00,
                'min_stock' => 8.00,
                'prep_time_minutes' => 30,
            ],
            [
                'name' => 'Chicken Malai Boti Platter',
                'code' => 'FP-BBQ-01',
                'sku' => 'FP-BBQ-01',
                'barcode' => '100008',
                'category_id' => $catMap['bbq-karahi']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPortion->id,
                'cost_price' => 340.00,
                'sale_price' => 650.00,
                'tax_percent' => 0.00,
                'current_stock' => 70.00,
                'min_stock' => 12.00,
                'prep_time_minutes' => 18,
            ],
            // Beverages
            [
                'name' => 'Soft Drink Can 345ml',
                'code' => 'FP-BEV-01',
                'sku' => 'FP-BEV-01',
                'barcode' => '100009',
                'category_id' => $catMap['beverages-shakes']->id,
                'brand_id' => $brandBeverage->id,
                'unit_id' => $unitPcs->id,
                'cost_price' => 75.00,
                'sale_price' => 120.00,
                'tax_percent' => 0.00,
                'current_stock' => 300.00,
                'min_stock' => 50.00,
                'prep_time_minutes' => 1,
            ],
            [
                'name' => 'Mint Lemonade Chiller',
                'code' => 'FP-BEV-02',
                'sku' => 'FP-BEV-02',
                'barcode' => '100010',
                'category_id' => $catMap['beverages-shakes']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPcs->id,
                'cost_price' => 90.00,
                'sale_price' => 250.00,
                'tax_percent' => 0.00,
                'current_stock' => 150.00,
                'min_stock' => 20.00,
                'prep_time_minutes' => 5,
            ],
            // Desserts
            [
                'name' => 'Chocolate Lava Cake',
                'code' => 'FP-DES-01',
                'sku' => 'FP-DES-01',
                'barcode' => '100011',
                'category_id' => $catMap['desserts-sweets']->id,
                'brand_id' => $brandKitchen->id,
                'unit_id' => $unitPcs->id,
                'cost_price' => 180.00,
                'sale_price' => 380.00,
                'tax_percent' => 0.00,
                'current_stock' => 45.00,
                'min_stock' => 10.00,
                'prep_time_minutes' => 8,
            ],
        ];

        $admin = User::where('email', 'admin@foodpoint.com')->first();

        foreach ($productsData as $p) {
            $prod = Product::firstOrCreate(['code' => $p['code']], $p);
            // Record initial opening stock in ledger
            StockMovement::firstOrCreate(
                ['product_id' => $prod->id, 'movement_type' => 'opening'],
                [
                    'quantity' => $prod->current_stock,
                    'unit_cost' => $prod->cost_price,
                    'balance_after' => $prod->current_stock,
                    'notes' => 'Initial opening stock initialization',
                    'user_id' => $admin?->id,
                ]
            );
        }

        // 6. Customers
        $customers = [
            [
                'name' => 'Walk-in Guest Customer',
                'mobile' => '0300-0000000',
                'address' => 'Counter / Takeaway',
                'area' => 'Counter',
                'credit_limit' => 0.00,
            ],
            [
                'name' => 'Muhammad Usman',
                'mobile' => '0300-1122334',
                'address' => 'House 42, St 5, Susan Road',
                'area' => 'Madina Town',
                'credit_limit' => 15000.00,
            ],
            [
                'name' => 'Tariq Mahmood',
                'mobile' => '0321-9988776',
                'address' => 'Villa 12, Block B',
                'area' => 'Peoples Colony',
                'credit_limit' => 20000.00,
            ],
            [
                'name' => 'Dr. Adnan Shah',
                'mobile' => '0333-5544332',
                'address' => 'Clinic 3, Medical Enclave',
                'area' => 'Kohinoor City',
                'credit_limit' => 50000.00,
            ],
        ];

        foreach ($customers as $cust) {
            Customer::firstOrCreate(['mobile' => $cust['mobile']], $cust);
        }

        // 7. Table Sections & Tables
        $hall = TableSection::firstOrCreate(['name' => 'Main Dining Hall'], ['description' => 'Ground floor general dining', 'is_active' => true]);
        $family = TableSection::firstOrCreate(['name' => 'Family Hall (AC)'], ['description' => 'Upper floor private family booths', 'is_active' => true]);
        $terrace = TableSection::firstOrCreate(['name' => 'Outdoor Terrace'], ['description' => 'Open-air garden dining', 'is_active' => true]);
        $vip = TableSection::firstOrCreate(['name' => 'VIP Executive Lounge'], ['description' => 'Private lounge for large gatherings', 'is_active' => true]);

        $tables = [
            ['table_number' => 'T-01', 'name' => 'Table 01', 'section_id' => $hall->id, 'capacity' => 4],
            ['table_number' => 'T-02', 'name' => 'Table 02', 'section_id' => $hall->id, 'capacity' => 4],
            ['table_number' => 'T-03', 'name' => 'Table 03', 'section_id' => $hall->id, 'capacity' => 6],
            ['table_number' => 'T-04', 'name' => 'Table 04', 'section_id' => $hall->id, 'capacity' => 2],
            ['table_number' => 'F-01', 'name' => 'Family Booth 01', 'section_id' => $family->id, 'capacity' => 6],
            ['table_number' => 'F-02', 'name' => 'Family Booth 02', 'section_id' => $family->id, 'capacity' => 8],
            ['table_number' => 'TR-01', 'name' => 'Terrace Table 01', 'section_id' => $terrace->id, 'capacity' => 4],
            ['table_number' => 'TR-02', 'name' => 'Terrace Table 02', 'section_id' => $terrace->id, 'capacity' => 4],
            ['table_number' => 'VIP-01', 'name' => 'VIP Board Table', 'section_id' => $vip->id, 'capacity' => 12],
        ];

        foreach ($tables as $t) {
            RestaurantTable::firstOrCreate(['table_number' => $t['table_number']], array_merge($t, ['status' => 'available', 'is_active' => true]));
        }

        // 8. Delivery Areas
        $areas = [
            ['name' => 'D-Ground Commercial', 'code' => 'DG', 'delivery_charge' => 80.00, 'estimated_distance_km' => 1.5],
            ['name' => 'Kohinoor City', 'code' => 'KC', 'delivery_charge' => 100.00, 'estimated_distance_km' => 2.8],
            ['name' => 'Madina Town', 'code' => 'MT', 'delivery_charge' => 120.00, 'estimated_distance_km' => 3.5],
            ['name' => 'Peoples Colony', 'code' => 'PC', 'delivery_charge' => 150.00, 'estimated_distance_km' => 5.0],
            ['name' => 'Canal Road / Gulberg', 'code' => 'CR', 'delivery_charge' => 200.00, 'estimated_distance_km' => 8.2],
        ];

        foreach ($areas as $a) {
            DeliveryArea::firstOrCreate(['code' => $a['code']], $a);
        }

        // 9. Delivery Riders
        $riders = [
            ['name' => 'Ali Raza', 'mobile' => '0301-4455667', 'employee_id' => 'RDR-101', 'vehicle_type' => 'Honda 125', 'vehicle_number' => 'FSD-7890', 'status' => 'available'],
            ['name' => 'Hamza Tariq', 'mobile' => '0322-7788990', 'employee_id' => 'RDR-102', 'vehicle_type' => 'Yamaha YBR', 'vehicle_number' => 'FSD-4321', 'status' => 'available'],
            ['name' => 'Bilal Ahmed', 'mobile' => '0345-1234888', 'employee_id' => 'RDR-103', 'vehicle_type' => 'Honda CD 70', 'vehicle_number' => 'FSD-9988', 'status' => 'available'],
        ];

        foreach ($riders as $r) {
            DeliveryRider::firstOrCreate(['employee_id' => $r['employee_id']], array_merge($r, ['joining_date' => now()->subMonths(6)]));
        }

        // 10. System Settings
        $settings = [
            ['key' => 'business_name', 'value' => 'Food Point Express & Restaurant', 'group' => 'general'],
            ['key' => 'business_address', 'value' => 'Main Commercial Boulevard, D-Ground, Faisalabad', 'group' => 'general'],
            ['key' => 'business_phone', 'value' => '+92 (41) 876-5432', 'group' => 'general'],
            ['key' => 'currency_code', 'value' => 'PKR', 'group' => 'general'],
            ['key' => 'currency_symbol', 'value' => 'Rs.', 'group' => 'general'],
            ['key' => 'tax_rate_percent', 'value' => '0', 'group' => 'finance'],
            ['key' => 'receipt_footer_note', 'value' => 'Thank you for dining with us! Please visit again.', 'group' => 'printing'],
        ];

        foreach ($settings as $s) {
            SystemSetting::firstOrCreate(['key' => $s['key']], $s);
        }
    }
}
