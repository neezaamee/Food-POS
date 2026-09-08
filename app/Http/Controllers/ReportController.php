<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\DeliveryRider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $query = Order::with(['items', 'customer', 'table', 'cashier'])
            ->where('order_status', 'completed')
            ->latest('finalized_at');

        if ($request->filled('from_date')) {
            $query->whereDate('finalized_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('finalized_at', '<=', $request->to_date);
        }

        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $orders = $query->paginate(20)->withQueryString();
        $totalSales = (clone $query)->sum('grand_total');
        $totalDiscount = (clone $query)->sum('discount_amount');
        $totalTax = (clone $query)->sum('tax_amount');
        $cashiers = User::all();

        return view('reports.sales', compact('orders', 'totalSales', 'totalDiscount', 'totalTax', 'cashiers'));
    }

    public function delivery(Request $request)
    {
        $query = Order::with(['rider', 'deliveryArea'])
            ->where('order_type', 'DELIVERY')
            ->where(function ($q) {
                $q->whereIn('order_status', ['completed', 'delivered'])
                    ->orWhereNotNull('finalized_at');
            })
            ->where('order_status', '!=', 'cancelled')
            ->orderByRaw('COALESCE(finalized_at, updated_at, created_at) DESC');

        if ($request->filled('from_date')) {
            $query->whereDate(DB::raw('COALESCE(finalized_at, created_at)'), '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate(DB::raw('COALESCE(finalized_at, created_at)'), '<=', $request->to_date);
        }

        if ($request->filled('rider_id')) {
            $query->where('delivery_rider_id', $request->rider_id);
        }

        $orders = $query->paginate(20)->withQueryString();
        $totalDeliveries = (clone $query)->count();
        $totalDeliveryCharges = (float) (clone $query)->sum('delivery_charge');
        $totalDeliveryKm = (float) (clone $query)->select(
            DB::raw('SUM(COALESCE(NULLIF(rider_total_km, 0), NULLIF(delivery_distance_km, 0), (SELECT estimated_distance_km FROM delivery_areas WHERE delivery_areas.id = orders.delivery_area_id), 0)) as total_km')
        )->value('total_km');

        $riders = DeliveryRider::all();

        // Calculate per-rider performance and mileage summary
        $riderStats = DeliveryRider::with(['orders' => function ($q) {
            $q->where(function ($oq) {
                $oq->whereIn('order_status', ['completed', 'delivered'])
                    ->orWhereNotNull('finalized_at');
            })->where('order_status', '!=', 'cancelled');
        }, 'orders.deliveryArea'])->get()->map(function ($rider) {
            $completedOrders = $rider->orders;
            $tripsCount = $completedOrders->count();
            $totalKm = $completedOrders->sum(function ($o) {
                return (float) ($o->rider_total_km > 0 ? $o->rider_total_km : ($o->delivery_distance_km > 0 ? $o->delivery_distance_km : ($o->deliveryArea?->estimated_distance_km ?: 0)));
            });
            $totalFees = (float) $completedOrders->sum('delivery_charge');
            $totalRevenue = (float) $completedOrders->sum('grand_total');
            $avgKm = $tripsCount > 0 ? ($totalKm / $tripsCount) : 0;

            return (object) [
                'rider' => $rider,
                'trips_count' => $tripsCount,
                'total_km' => $totalKm,
                'total_fees' => $totalFees,
                'total_revenue' => $totalRevenue,
                'avg_km' => $avgKm,
            ];
        });

        return view('reports.delivery', compact('orders', 'totalDeliveries', 'totalDeliveryCharges', 'totalDeliveryKm', 'riders', 'riderStats'));
    }

    public function tables()
    {
        $tables = RestaurantTable::withCount(['activeOrder'])
            ->get();

        $tableStats = Order::where('order_type', 'DINE_IN')
            ->where('order_status', 'completed')
            ->select('table_name', DB::raw('count(*) as total_orders'), DB::raw('sum(grand_total) as total_revenue'), DB::raw('avg(grand_total) as avg_bill'))
            ->groupBy('table_name')
            ->get();

        return view('reports.tables', compact('tables', 'tableStats'));
    }

    public function products(Request $request)
    {
        $productStats = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.order_status', 'completed')
            ->select(
                'order_items.product_name',
                DB::raw('sum(order_items.quantity) as total_qty'),
                DB::raw('sum(order_items.subtotal) as gross_sales')
            )
            ->groupBy('order_items.product_name')
            ->orderByDesc('gross_sales')
            ->paginate(20);

        return view('reports.products', compact('productStats'));
    }

    public function payments(Request $request)
    {
        $paymentStats = OrderPayment::select(
            'payment_method',
            DB::raw('count(*) as transaction_count'),
            DB::raw('sum(amount) as total_collected')
        )
            ->groupBy('payment_method')
            ->get();

        $totalCollected = $paymentStats->sum('total_collected');

        return view('reports.payments', compact('paymentStats', 'totalCollected'));
    }

    public function customerLedger(Request $request)
    {
        $customers = Customer::orderBy('name')->get();
        $selectedCustomer = null;
        $customerOrders = collect();

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->customer_id);
            if ($selectedCustomer) {
                $customerOrders = Order::where('customer_id', $selectedCustomer->id)
                    ->latest()
                    ->get();
            }
        }

        return view('reports.customer-ledger', compact('customers', 'selectedCustomer', 'customerOrders'));
    }

    /**
     * Day-Wise Sales Report:
     * Consolidates all shifts of each day (e.g. Lunch Shift 1 + Dinner Shift 2)
     * into a single unified daily sales figure with shift drill-downs.
     */
    public function dailySales(Request $request)
    {
        $fromDate = $request->input('from_date', now()->subDays(30)->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        // Group completed orders by DATE(created_at)
        $dailyRecords = Order::where('order_status', 'completed')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->select(
                DB::raw('DATE(created_at) as sale_date'),
                DB::raw('count(*) as total_orders'),
                DB::raw('sum(grand_total) as gross_sales'),
                DB::raw('sum(discount_amount) as total_discount'),
                DB::raw('sum(tax_amount) as total_tax'),
                DB::raw('sum(paid_amount) as net_collected')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderByDesc('sale_date')
            ->paginate(15)
            ->withQueryString();

        // For each day, attach all shifts that operated on that date
        $dailyRecords->getCollection()->transform(function ($day) {
            $day->shifts = CashShift::with(['user', 'orders'])
                ->whereDate('opened_at', $day->sale_date)
                ->orderBy('opened_at')
                ->get();

            // Calculate cash vs digital breakdown for this day
            $day->cash_sales = OrderPayment::join('orders', 'order_payments.order_id', '=', 'orders.id')
                ->where('orders.order_status', 'completed')
                ->whereDate('orders.created_at', $day->sale_date)
                ->where('order_payments.payment_method', 'cash')
                ->sum('order_payments.amount');

            $day->digital_sales = OrderPayment::join('orders', 'order_payments.order_id', '=', 'orders.id')
                ->where('orders.order_status', 'completed')
                ->whereDate('orders.created_at', $day->sale_date)
                ->where('order_payments.payment_method', '!=', 'cash')
                ->sum('order_payments.amount');

            return $day;
        });

        // Summary metrics across the filtered period
        $periodGrossSales = (float) Order::where('order_status', 'completed')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->sum('grand_total');

        $periodOrdersCount = Order::where('order_status', 'completed')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->count();

        $periodNetCollected = (float) Order::where('order_status', 'completed')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->sum('paid_amount');

        return view('reports.daily-sales', compact(
            'dailyRecords',
            'fromDate',
            'toDate',
            'periodGrossSales',
            'periodOrdersCount',
            'periodNetCollected'
        ));
    }

    /**
     * Shift-Wise Sales Report:
     * Individual breakdown per cashier shift session with cash drawer reconciliation.
     */
    public function shiftSales(Request $request)
    {
        $query = CashShift::with(['user', 'orders.items'])
            ->latest('opened_at');

        if ($request->filled('from_date')) {
            $query->whereDate('opened_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('opened_at', '<=', $request->to_date);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $shifts = $query->paginate(15)->withQueryString();
        $cashiers = User::all();

        $totalShiftSales = (float) CashShift::where('status', 'closed')->sum('cash_sales');
        $totalDifference = (float) CashShift::where('status', 'closed')->sum('difference');

        return view('reports.shift-sales', compact('shifts', 'cashiers', 'totalShiftSales', 'totalDifference'));
    }
}
