<?php

namespace App\Http\Controllers;

use App\Models\DeliveryArea;
use App\Models\DeliveryRider;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Models\TableSection;
use App\Services\SaaS\SubscriptionService;
use App\Services\SaaS\TenantContext;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    // Tables & Sections Management
    public function tables()
    {
        $sections = TableSection::with(['tables.activeOrder'])->get();

        return view('restaurant.tables', compact('sections'));
    }

    public function storeSection(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        TableSection::create($request->only('name', 'description'));

        return back()->with('success', 'Table Section created successfully!');
    }

    public function storeTable(Request $request)
    {
        if (! app(SubscriptionService::class)->canCreateTable()) {
            return back()->with('error', 'You have reached the maximum tables limit allowed by your subscription plan. Please upgrade to add more tables.');
        }

        $tenantId = TenantContext::id() ?? 1;

        $request->validate([
            'table_number' => "required|string|max:50|unique:tables,table_number,NULL,id,tenant_id,{$tenantId}",
            'name' => 'required|string|max:100',
            'section_id' => 'required|exists:table_sections,id',
            'capacity' => 'required|integer|min:1',
        ]);

        RestaurantTable::create(array_merge($request->only('table_number', 'name', 'section_id', 'capacity'), ['status' => 'available']));

        return back()->with('success', 'Restaurant Table added successfully!');
    }

    // Kitchen Order Ticket (KOT) & Kitchen Display Screen (KDS)
    public function kitchen(Request $request)
    {
        $activeOrders = Order::with(['items', 'table'])
            ->whereNull('finalized_at')
            ->where('order_status', '!=', 'cancelled')
            ->latest()
            ->get();

        return view('restaurant.kitchen', compact('activeOrders'));
    }

    public function updateOrderStatus(Request $request, int $orderId)
    {
        $order = Order::findOrFail($orderId);
        $status = $request->input('kot_status') ?? $request->input('status');
        if ($status) {
            $order->kot_status = $status;
            $order->save();
        }

        return back()->with('success', "Order #{$order->order_number} status updated to ".ucfirst($order->kot_status));
    }

    public function updateItemStatus(Request $request, int $itemId)
    {
        $item = OrderItem::findOrFail($itemId);
        $item->status = $request->status ?? 'ready';
        $item->save();

        return back()->with('success', "Item '{$item->product_name}' status updated to ".ucfirst($item->status));
    }

    // Printable Kitchen Order Ticket (Thermal 80mm format)
    public function kotPrint(Request $request, int $orderId)
    {
        $order = Order::with(['items.product', 'table', 'deliveryArea', 'kots.items.product'])->findOrFail($orderId);

        $targetKot = null;
        if ($request->filled('kot_id')) {
            $targetKot = $order->kots()->with('items.product')->find($request->kot_id);
        } elseif ($request->filled('kot_number')) {
            $targetKot = $order->kots()->with('items.product')->where('kot_number', $request->kot_number)->first();
        }

        // If no specific kot requested, but kots exist, take the latest kot, or default to all order items
        if (! $targetKot && $order->kots->isNotEmpty()) {
            $targetKot = $order->latestKot();
        }

        return view('restaurant.kot', compact('order', 'targetKot'));
    }

    // Delivery Areas Management
    public function deliveryAreas()
    {
        $areas = DeliveryArea::withCount('orders')->latest()->get();

        return view('restaurant.delivery', compact('areas'));
    }

    public function storeDeliveryArea(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|unique:delivery_areas,code|max:30',
            'delivery_charge' => 'required|numeric|min:0',
            'estimated_distance_km' => 'required|numeric|min:0',
        ]);

        DeliveryArea::create($request->only('name', 'code', 'delivery_charge', 'estimated_distance_km'));

        return back()->with('success', 'Delivery Area created successfully!');
    }

    // Delivery Riders Management
    public function riders()
    {
        $riders = DeliveryRider::withCount('orders')->latest()->get();

        return view('restaurant.riders', compact('riders'));
    }

    public function storeRider(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'mobile' => 'required|string|max:30',
            'employee_id' => 'required|string|unique:delivery_riders,employee_id|max:50',
            'vehicle_type' => 'required|string|max:50',
            'vehicle_number' => 'nullable|string|max:50',
        ]);

        DeliveryRider::create(array_merge($request->only('name', 'mobile', 'employee_id', 'vehicle_type', 'vehicle_number'), [
            'status' => 'available',
            'joining_date' => now()->toDateString(),
        ]));

        return back()->with('success', 'Delivery Rider added successfully!');
    }

    public function updateOrderMileage(Request $request, int $orderId)
    {
        $request->validate([
            'rider_starting_km' => 'nullable|numeric|min:0',
            'rider_ending_km' => 'nullable|numeric|min:0',
            'rider_total_km' => 'nullable|numeric|min:0',
            'delivery_rider_id' => 'nullable|exists:delivery_riders,id',
        ]);

        $order = Order::findOrFail($orderId);
        if ($request->filled('delivery_rider_id')) {
            $order->delivery_rider_id = $request->delivery_rider_id;
        }

        $start = $request->filled('rider_starting_km') ? (float) $request->rider_starting_km : $order->rider_starting_km;
        $end = $request->filled('rider_ending_km') ? (float) $request->rider_ending_km : $order->rider_ending_km;
        $total = $request->filled('rider_total_km') ? (float) $request->rider_total_km : null;

        if ($start !== null) {
            $order->rider_starting_km = $start;
        }
        if ($end !== null) {
            $order->rider_ending_km = $end;
        }

        if ($total !== null && $total > 0) {
            $order->rider_total_km = $total;
        } elseif ($start !== null && $end !== null && $end >= $start) {
            $order->rider_total_km = $end - $start;
        }

        $order->save();

        return back()->with('success', "Mileage for Order #{$order->order_number} updated successfully!");
    }
}
