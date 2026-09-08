<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Models\DayClose;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Http\Request;

class DayCloseController extends Controller
{
    /**
     * Display Day Close management & overview screen
     */
    public function index(Request $request)
    {
        $selectedDate = $request->input('date', now()->toDateString());

        $existingDayClose = DayClose::whereDate('business_date', $selectedDate)->first();

        $shifts = CashShift::whereDate('created_at', $selectedDate)
            ->with(['user', 'transactions'])
            ->orderBy('id', 'asc')
            ->get();

        $hasOpenShifts = $shifts->contains(fn ($s) => $s->isOpen());

        $completedOrders = Order::whereDate('created_at', $selectedDate)
            ->where('order_status', 'completed')
            ->with(['payments'])
            ->get();

        $orderIds = $completedOrders->pluck('id');

        // Aggregated Sales Data
        $grossSales = (float) $completedOrders->sum('subtotal');
        $totalDiscount = (float) $completedOrders->sum('discount_amount');
        $totalTax = (float) $completedOrders->sum('tax_amount');
        $totalDelivery = (float) $completedOrders->sum('delivery_charge');
        $netSales = (float) $completedOrders->sum('grand_total');

        // Payment Breakdown
        $cashSales = (float) OrderPayment::whereIn('order_id', $orderIds)
            ->where('payment_method', 'cash')
            ->sum('amount');

        $digitalSales = (float) OrderPayment::whereIn('order_id', $orderIds)
            ->whereIn('payment_method', ['bank', 'card', 'digital'])
            ->sum('amount');

        $creditSales = (float) $completedOrders->sum('balance_amount');

        // Cash Drawer Aggregation across shifts
        $openingCashTotal = (float) $shifts->sum('opening_cash');
        $expectedCashTotal = (float) $shifts->sum('expected_cash');
        $actualCashTotal = (float) $shifts->where('status', 'closed')->sum('actual_cash');
        $differenceTotal = (float) $shifts->where('status', 'closed')->sum('difference');
        $totalRefunds = (float) $shifts->sum('refunds');

        // Past Day Closes History
        $pastDayCloses = DayClose::with('closedByUser')
            ->orderBy('business_date', 'desc')
            ->paginate(10);

        return view('cash.day-close', compact(
            'selectedDate',
            'existingDayClose',
            'shifts',
            'hasOpenShifts',
            'completedOrders',
            'grossSales',
            'totalDiscount',
            'totalTax',
            'totalDelivery',
            'netSales',
            'cashSales',
            'digitalSales',
            'creditSales',
            'openingCashTotal',
            'expectedCashTotal',
            'actualCashTotal',
            'differenceTotal',
            'totalRefunds',
            'pastDayCloses'
        ));
    }

    /**
     * Perform Day Close settlement (Z-Close)
     */
    public function closeDay(Request $request)
    {
        $user = auth()->user();
        if ($user && ! $user->canCancelOrder()) {
            return back()->with('error', 'Unauthorized! Only Managers, Owners, and Super Admins have permission to perform Day Close.');
        }

        $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $date = $request->input('date');

        if (DayClose::whereDate('business_date', $date)->exists()) {
            return back()->with('error', "Day Close has already been completed for {$date}!");
        }

        $shifts = CashShift::whereDate('created_at', $date)->get();

        if ($shifts->isEmpty()) {
            return back()->with('error', "No cashier shifts were found on {$date} to settle.");
        }

        $openShift = $shifts->firstWhere('status', 'open');
        if ($openShift) {
            return back()->with('error', "Cannot perform Day Close! Shift #{$openShift->id} is still OPEN. Please ensure all shifts for today (Shift 1, Shift 2, etc.) are closed first.");
        }

        $completedOrders = Order::whereDate('created_at', $date)
            ->where('order_status', 'completed')
            ->get();

        $orderIds = $completedOrders->pluck('id');

        $grossSales = (float) $completedOrders->sum('subtotal');
        $totalDiscount = (float) $completedOrders->sum('discount_amount');
        $totalTax = (float) $completedOrders->sum('tax_amount');
        $totalDelivery = (float) $completedOrders->sum('delivery_charge');
        $netSales = (float) $completedOrders->sum('grand_total');

        $cashSales = (float) OrderPayment::whereIn('order_id', $orderIds)
            ->where('payment_method', 'cash')
            ->sum('amount');

        $digitalSales = (float) OrderPayment::whereIn('order_id', $orderIds)
            ->whereIn('payment_method', ['bank', 'card', 'digital'])
            ->sum('amount');

        $creditSales = (float) $completedOrders->sum('balance_amount');

        $openingCashTotal = (float) $shifts->sum('opening_cash');
        $expectedCashTotal = (float) $shifts->sum('expected_cash');
        $actualCashTotal = (float) $shifts->sum('actual_cash');
        $differenceTotal = (float) $shifts->sum('difference');
        $totalRefunds = (float) $shifts->sum('refunds');

        $dayClose = DayClose::create([
            'business_date' => $date,
            'closed_by' => auth()->id(),
            'total_shifts_count' => $shifts->count(),
            'total_orders_count' => $completedOrders->count(),
            'gross_sales' => $grossSales,
            'discount_amount' => $totalDiscount,
            'tax_amount' => $totalTax,
            'delivery_charges' => $totalDelivery,
            'net_sales' => $netSales,
            'cash_sales' => $cashSales,
            'digital_sales' => $digitalSales,
            'credit_sales' => $creditSales,
            'total_refunds' => $totalRefunds,
            'opening_cash_total' => $openingCashTotal,
            'expected_cash_total' => $expectedCashTotal,
            'actual_cash_total' => $actualCashTotal,
            'difference_total' => $differenceTotal,
            'shift_ids' => $shifts->pluck('id')->toArray(),
            'notes' => $request->input('notes'),
            'closed_at' => now(),
        ]);

        return redirect()->route('cash.day-close.z-report', $dayClose->id)
            ->with('success', "Day Close for {$date} settled successfully! Z-Report is ready for printing.");
    }

    /**
     * Printable End of Day Z-Report
     */
    public function zReport(int $id)
    {
        $dayClose = DayClose::with('closedByUser')->findOrFail($id);
        $shifts = CashShift::whereIn('id', $dayClose->shift_ids ?? [])->with('user')->get();

        $completedOrders = Order::whereDate('created_at', $dayClose->business_date)
            ->where('order_status', 'completed')
            ->get();

        return view('cash.z-report', compact('dayClose', 'shifts', 'completedOrders'));
    }
}
