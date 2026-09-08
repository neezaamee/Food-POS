<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Order;
use App\Models\Product;
use App\Models\RestaurantTable;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        // Real Database KPIs
        $todaySales = Order::whereDate('finalized_at', $today)
            ->where('order_status', 'completed')
            ->sum('grand_total');

        $takeawaySales = Order::whereDate('finalized_at', $today)
            ->where('order_status', 'completed')
            ->where('order_type', 'TAKEAWAY')
            ->sum('grand_total');

        $dineInSales = Order::whereDate('finalized_at', $today)
            ->where('order_status', 'completed')
            ->where('order_type', 'DINE_IN')
            ->sum('grand_total');

        $deliverySales = Order::whereDate('finalized_at', $today)
            ->where('order_status', 'completed')
            ->where('order_type', 'DELIVERY')
            ->sum('grand_total');

        $todayOrdersCount = Order::whereDate('finalized_at', $today)
            ->where('order_status', 'completed')
            ->count();

        $openOrdersCount = Order::whereNull('finalized_at')
            ->where('order_status', '!=', 'cancelled')
            ->count();

        $openTablesCount = RestaurantTable::where('status', 'occupied')->count();
        $totalTablesCount = RestaurantTable::count();

        $lowStockProducts = Product::whereColumn('current_stock', '<=', 'min_stock')->get();
        $lowStockCount = $lowStockProducts->count();

        // Recent Orders
        $recentOrders = Order::with(['items', 'table', 'customer'])
            ->latest()
            ->take(8)
            ->get();

        // Order Type Distribution
        $typeDistribution = [
            'takeaway' => (float) $takeawaySales,
            'dine_in' => (float) $dineInSales,
            'delivery' => (float) $deliverySales,
        ];

        // Active Cash Shift
        $activeShift = CashShift::where('status', 'open')->latest()->first();

        return view('dashboard', compact(
            'todaySales',
            'takeawaySales',
            'dineInSales',
            'deliverySales',
            'todayOrdersCount',
            'openOrdersCount',
            'openTablesCount',
            'totalTablesCount',
            'lowStockCount',
            'lowStockProducts',
            'recentOrders',
            'typeDistribution',
            'activeShift'
        ));
    }
}
