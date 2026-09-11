<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\SaleReturn;
use App\Services\Sales\OrderService;
use App\Services\Sales\SaleReturnService;
use App\Services\WhatsApp\WhatsAppService;
use Exception;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items', 'customer', 'table', 'cashier'])
            ->latest();

        if ($request->filled('type')) {
            $query->where('order_type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'completed') {
                $query->whereNotNull('finalized_at');
            } elseif ($request->status === 'draft') {
                $query->whereNull('finalized_at');
            }
        }

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                    ->orWhere('customer_name', 'like', $term)
                    ->orWhere('customer_phone', 'like', $term)
                    ->orWhere('table_name', 'like', $term);
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('orders.index', compact('orders'));
    }

    public function show(int $id)
    {
        $order = Order::with(['items.product', 'items.deal.items.product', 'payments.account', 'customer', 'table', 'deliveryArea', 'rider', 'cashier', 'returns.items'])
            ->findOrFail($id);

        return view('orders.show', compact('order'));
    }

    public function thermal(int $id)
    {
        $order = Order::with(['items.deal.items.product', 'payments', 'customer', 'table', 'rider', 'cashier'])
            ->findOrFail($id);

        return view('orders.thermal', compact('order'));
    }

    public function sendWhatsApp(Request $request, int $id, WhatsAppService $whatsAppService)
    {
        $order = Order::with(['items.deal.items.product', 'payments', 'customer', 'table', 'rider', 'cashier', 'deliveryArea'])
            ->findOrFail($id);

        $phone = $request->input('phone', $order->customer_phone);

        if (empty($phone)) {
            return back()->with('error', 'Please provide a valid customer WhatsApp phone number.');
        }

        $result = $whatsAppService->sendReceipt($order, $phone);

        if ($result['ok'] ?? false) {
            return back()->with('success', 'Order receipt sent to customer via WhatsApp (+'.$result['recipient'].') successfully!');
        }

        $errorMsg = $result['error'] ?? 'Failed to send WhatsApp message.';
        if (! empty($result['fallback_url'])) {
            session()->flash('whatsapp_fallback_url', $result['fallback_url']);
        }

        return back()->with('error', $errorMsg);
    }

    // Sale Returns List
    public function returnsIndex()
    {
        $returns = SaleReturn::with(['order', 'customer', 'items.product', 'user'])
            ->latest()
            ->paginate(15);

        return view('orders.returns', compact('returns'));
    }

    // Process Return POST
    public function processReturn(Request $request, int $orderId, SaleReturnService $returnService)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'refund_method' => 'required|in:cash,credit,bank',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|exists:order_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        try {
            $order = Order::findOrFail($orderId);
            $saleReturn = $returnService->createReturn(
                $order,
                $request->items,
                $request->reason,
                $request->refund_method
            );

            return redirect()->route('orders.show', $orderId)
                ->with('success', "Sale Return #{$saleReturn->return_number} processed successfully! Stock has been returned to inventory and accounting reversed.");
        } catch (Exception $e) {
            return back()->with('error', 'Failed to process return: '.$e->getMessage());
        }
    }

    // Cancel Order (Draft or Completed) with shift closure and role checks
    public function cancelOrder(Request $request, int $id, OrderService $orderService)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
            'is_waste' => 'nullable|boolean',
        ]);

        $order = Order::findOrFail($id);

        try {
            $isWaste = (bool) $request->input('is_waste', false);
            $orderService->cancelOrder($order, $request->reason, auth()->user(), $isWaste);

            return redirect()->route('orders.show', $order->id)
                ->with('success', "Order #{$order->order_number} has been cancelled successfully.");
        } catch (Exception $e) {
            return back()->with('error', 'Cancellation failed: '.$e->getMessage());
        }
    }
}
