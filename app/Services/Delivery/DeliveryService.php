<?php

namespace App\Services\Delivery;

use App\Models\AuditLog;
use App\Models\DeliveryArea;
use App\Models\DeliveryRider;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DeliveryService
{
    /**
     * Set delivery area and default charge for an order
     */
    public function setDeliveryArea(Order $order, DeliveryArea $area, ?float $customCharge = null, ?int $userId = null): void
    {
        $oldCharge = $order->delivery_charge;
        $charge = $customCharge !== null ? $customCharge : (float) $area->delivery_charge;

        $order->delivery_area_id = $area->id;
        $order->delivery_charge = $charge;
        $order->delivery_distance_km = $area->estimated_distance_km;

        // Recalculate grand total
        $order->grand_total = (float) $order->subtotal - (float) $order->discount_amount + (float) $order->tax_amount + $charge;
        $order->balance_amount = $order->grand_total - (float) $order->paid_amount;
        $order->save();

        if ($customCharge !== null && abs($customCharge - (float) $area->delivery_charge) > 0.01) {
            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => 'Delivery Charge Override',
                'module' => 'Delivery',
                'record_id' => $order->id,
                'old_value' => ['default_charge' => $area->delivery_charge],
                'new_value' => ['overridden_charge' => $customCharge],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }

    /**
     * Assign a rider to a delivery order
     */
    public function assignRider(Order $order, DeliveryRider $rider, ?float $startingKm = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($order, $rider, $startingKm, $userId) {
            $order->delivery_rider_id = $rider->id;
            $order->order_status = 'assigned';

            if ($startingKm !== null) {
                $order->rider_starting_km = $startingKm;
            }

            $order->save();

            $rider->status = 'assigned';
            $rider->save();

            AuditLog::create([
                'user_id' => $userId ?? auth()->id(),
                'action' => 'Rider Assigned',
                'module' => 'Delivery',
                'record_id' => $order->id,
                'old_value' => ['rider_id' => null],
                'new_value' => ['rider_id' => $rider->id, 'rider_name' => $rider->name, 'starting_km' => $startingKm],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Dispatch order out for delivery
     */
    public function dispatchOrder(Order $order): void
    {
        $order->order_status = 'out_for_delivery';
        $order->save();

        if ($order->rider) {
            $order->rider->status = 'on_delivery';
            $order->rider->save();
        }
    }

    /**
     * Complete delivery with ending KM and calculate rider total mileage
     */
    public function completeDelivery(Order $order, ?float $endingKm = null): void
    {
        DB::transaction(function () use ($order, $endingKm) {
            $order->order_status = 'delivered';

            if ($endingKm !== null) {
                $order->rider_ending_km = $endingKm;
                $start = (float) ($order->rider_starting_km ?? $endingKm);
                $order->rider_total_km = max(0, $endingKm - $start);
            }

            $order->save();

            if ($order->rider) {
                $order->rider->status = 'available';
                $order->rider->save();
            }
        });
    }
}
