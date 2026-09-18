<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\Customer;
use App\Models\DeliveryRider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\RestaurantTable;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
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

    public function customerLedger(Request $request, WhatsAppService $whatsAppService)
    {
        $customers = Customer::orderBy('name')->get();
        $selectedCustomer = null;
        $ledgerEntries = collect();
        $summary = [
            'opening_balance' => 0.00,
            'total_invoiced' => 0.00,
            'total_paid' => 0.00,
            'total_returns' => 0.00,
            'remaining_balance' => 0.00,
        ];
        $whatsAppStatus = $whatsAppService->getStatus();

        if ($request->filled('customer_id')) {
            $selectedCustomer = Customer::find($request->customer_id);

            if ($selectedCustomer) {
                // Ensure current balance is synchronized
                $selectedCustomer->syncBalance();

                $initialOpening = (float) $selectedCustomer->opening_balance;
                $fromDate = $request->input('from_date');
                $toDate = $request->input('to_date');

                // If date range is specified, calculate opening balance prior to from_date
                if ($fromDate) {
                    $priorOrdersDebit = (float) Order::where('customer_id', $selectedCustomer->id)
                        ->where('order_status', '!=', 'cancelled')
                        ->whereDate('created_at', '<', $fromDate)
                        ->sum('grand_total');

                    $priorOrdersCredit = (float) Order::where('customer_id', $selectedCustomer->id)
                        ->where('order_status', '!=', 'cancelled')
                        ->whereDate('created_at', '<', $fromDate)
                        ->sum('paid_amount');

                    $priorReturns = (float) SaleReturn::where('customer_id', $selectedCustomer->id)
                        ->whereDate('created_at', '<', $fromDate)
                        ->sum('grand_total');

                    $periodOpeningBalance = $initialOpening + ($priorOrdersDebit - $priorOrdersCredit) - $priorReturns;
                } else {
                    $periodOpeningBalance = $initialOpening;
                }

                // Query Orders in period
                $ordersQuery = Order::where('customer_id', $selectedCustomer->id)
                    ->where('order_status', '!=', 'cancelled');

                if ($fromDate) {
                    $ordersQuery->whereDate('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $ordersQuery->whereDate('created_at', '<=', $toDate);
                }

                $orders = $ordersQuery->orderBy('created_at')->orderBy('id')->get();

                // Query Sale Returns in period
                $returnsQuery = SaleReturn::where('customer_id', $selectedCustomer->id);

                if ($fromDate) {
                    $returnsQuery->whereDate('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $returnsQuery->whereDate('created_at', '<=', $toDate);
                }

                $returns = $returnsQuery->orderBy('created_at')->orderBy('id')->get();

                // Compile transactions into a unified chronological ledger
                foreach ($orders as $order) {
                    $ledgerEntries->push((object) [
                        'id' => 'order_'.$order->id,
                        'date' => $order->created_at,
                        'type' => 'invoice',
                        'type_label' => 'INVOICE ('.$order->order_type.')',
                        'reference' => $order->order_number,
                        'order_id' => $order->id,
                        'description' => 'Invoice #'.$order->order_number.($order->table_name ? ' (Table: '.$order->table_name.')' : ''),
                        'debit' => (float) $order->grand_total,
                        'credit' => (float) $order->paid_amount,
                        'net' => (float) ($order->grand_total - $order->paid_amount),
                        'payment_status' => $order->payment_status,
                    ]);
                }

                foreach ($returns as $return) {
                    $ledgerEntries->push((object) [
                        'id' => 'return_'.$return->id,
                        'date' => $return->created_at,
                        'type' => 'return',
                        'type_label' => 'SALE RETURN',
                        'reference' => $return->return_number,
                        'order_id' => $return->order_id,
                        'description' => 'Sale Return #'.$return->return_number.($return->order ? ' (Ref: '.$return->order->order_number.')' : ''),
                        'debit' => 0.00,
                        'credit' => (float) $return->grand_total,
                        'net' => -((float) $return->grand_total),
                        'payment_status' => 'refunded',
                    ]);
                }

                // Sort chronologically
                $ledgerEntries = $ledgerEntries->sortBy('date')->values();

                // Calculate running remaining balance row-by-row
                $runningBalance = $periodOpeningBalance;
                $totalInvoiced = 0.00;
                $totalPaid = 0.00;

                foreach ($ledgerEntries as $entry) {
                    $totalInvoiced += $entry->debit;
                    $totalPaid += $entry->credit;
                    $runningBalance += ($entry->debit - $entry->credit);
                    $entry->remaining_balance = $runningBalance;
                }

                $summary = [
                    'opening_balance' => $periodOpeningBalance,
                    'total_invoiced' => (float) $orders->sum('grand_total'),
                    'total_paid' => (float) $orders->sum('paid_amount'),
                    'total_returns' => (float) $returns->sum('grand_total'),
                    'remaining_balance' => $runningBalance,
                ];
            }
        }

        return view('reports.customer-ledger', compact('customers', 'selectedCustomer', 'ledgerEntries', 'summary', 'whatsAppStatus'));
    }

    /**
     * Share customer balance statement via WhatsApp.
     * Verifies connection state first; returns validation error if not connected.
     */
    public function shareCustomerBalanceWhatsApp(Request $request, WhatsAppService $whatsAppService)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'phone' => 'nullable|string|max:30',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $phone = $request->filled('phone') ? trim($request->phone) : $customer->mobile;

        if (empty($phone)) {
            return back()->with('error', 'Please provide a valid customer WhatsApp mobile number.');
        }

        // Calculate current financial summary
        $customer->syncBalance();
        $orders = Order::where('customer_id', $customer->id)
            ->where('order_status', '!=', 'cancelled')
            ->get();
        $returns = SaleReturn::where('customer_id', $customer->id)->get();

        $summary = [
            'opening_balance' => (float) $customer->opening_balance,
            'total_invoiced' => (float) $orders->sum('grand_total'),
            'total_paid' => (float) $orders->sum('paid_amount'),
            'total_returns' => (float) $returns->sum('grand_total'),
            'remaining_balance' => (float) $customer->current_balance,
        ];

        // 1. FIRSTLY CHECK WHATSAPP CONNECTION STATUS
        $status = $whatsAppService->getStatus();
        if (! ($status['connected'] ?? false)) {
            $msg = $whatsAppService->formatCustomerBalanceMessage($customer, $summary);
            $fallbackUrl = $whatsAppService->getWhatsAppWebUrl($phone, $msg);
            session()->flash('whatsapp_fallback_url', $fallbackUrl);

            return back()->with('error', 'WhatsApp integration is not connected. Please scan the QR code and pair your WhatsApp device in Settings > WhatsApp first.');
        }

        // 2. Dispatch via WhatsApp service
        $result = $whatsAppService->sendCustomerBalanceStatement($customer, $phone, $summary);

        if (($result['ok'] ?? false) || ($result['success'] ?? false)) {
            return back()->with('success', "Customer balance statement successfully dispatched via WhatsApp to {$phone}.");
        }

        if (! empty($result['fallback_url'])) {
            session()->flash('whatsapp_fallback_url', $result['fallback_url']);
        }

        return back()->with('error', $result['error'] ?? 'Failed to send WhatsApp message.');
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
