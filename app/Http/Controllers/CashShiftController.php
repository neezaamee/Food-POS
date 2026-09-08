<?php

namespace App\Http\Controllers;

use App\Models\CashShift;
use App\Services\Cash\CashShiftService;
use Exception;
use Illuminate\Http\Request;

class CashShiftController extends Controller
{
    public function index(CashShiftService $shiftService)
    {
        $activeShift = $shiftService->getActiveShift();
        $pastShifts = CashShift::with('user')->latest()->paginate(15);

        return view('cash.shifts', compact('activeShift', 'pastShifts'));
    }

    public function open(Request $request, CashShiftService $shiftService)
    {
        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        try {
            $shift = $shiftService->openShift(
                (float) $request->opening_cash,
                $request->notes
            );

            return back()->with('success', "Cash Drawer Shift #{$shift->id} opened with Rs. ".number_format($shift->opening_cash).' float!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function close(Request $request, int $id, CashShiftService $shiftService)
    {
        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
        ]);

        try {
            $shift = CashShift::findOrFail($id);
            $closedShift = $shiftService->closeShift(
                $shift,
                (float) $request->actual_cash,
                $request->notes
            );

            $msg = "Shift #{$closedShift->id} closed successfully! Expected: Rs. ".number_format($closedShift->expected_cash).' | Counted: Rs. '.number_format($closedShift->actual_cash).' | Difference: Rs. '.number_format($closedShift->difference);

            return back()->with('success', $msg);
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function transaction(Request $request, int $id, CashShiftService $shiftService)
    {
        $request->validate([
            'type' => 'required|in:cash_in,cash_out',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        try {
            $shift = CashShift::findOrFail($id);
            $shiftService->addTransaction(
                $shift,
                $request->type,
                (float) $request->amount,
                $request->description
            );

            return back()->with('success', 'Drawer '.($request->type === 'cash_in' ? 'Pay-In' : 'Pay-Out').' recorded successfully!');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
