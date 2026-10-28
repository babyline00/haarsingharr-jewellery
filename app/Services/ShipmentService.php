<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public const STATUSES = [
        'pending', 'picked_up', 'in_transit', 'out_for_delivery',
        'delivered', 'failed', 'returned',
    ];

    public function create(Order $order, array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($order, $data, $actor) {
            $shipment = Shipment::create(array_merge($data, [
                'order_id' => $order->id,
                'status' => $data['status'] ?? 'picked_up',
                'shipped_at' => $data['shipped_at'] ?? now(),
            ]));

            $this->syncOrder($order, $shipment, $actor);

            return $shipment;
        });
    }

    public function update(Shipment $shipment, array $data, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $data, $actor) {
            $shipment->fill($data);

            if ($shipment->status === 'delivered' && ! $shipment->delivered_at) {
                $shipment->delivered_at = now();
            }

            if (in_array($shipment->status, ['picked_up', 'in_transit', 'out_for_delivery'], true)
                && ! $shipment->shipped_at) {
                $shipment->shipped_at = now();
            }

            $shipment->save();

            $this->syncOrder($shipment->order()->firstOrFail(), $shipment, $actor);

            return $shipment;
        });
    }

    public function remove(Shipment $shipment): void
    {
        DB::transaction(function () use ($shipment) {
            $order = $shipment->order()->first();
            $shipment->delete();

            if ($order) {
                $remaining = $order->shipments()->latest('id')->first();

                $order->update([
                    'courier' => $remaining?->courier,
                    'tracking_number' => $remaining?->tracking_number,
                    'tracking_url' => $remaining?->tracking_url,
                ]);
            }
        });
    }

    public function syncOrder(Order $order, Shipment $shipment, ?User $actor = null): void
    {
        $order->update([
            'courier' => $shipment->courier,
            'tracking_number' => $shipment->tracking_number,
            'tracking_url' => $shipment->tracking_url,
        ]);

        if ($shipment->status === 'delivered') {
            $order->update(['delivered_at' => $shipment->delivered_at ?? now()]);

            if ($order->status->canTransitionTo(OrderStatus::Delivered)) {
                $order->transitionTo(OrderStatus::Delivered, $actor);
            }
        }

        if ($shipment->status === 'returned' && $order->status->canTransitionTo(OrderStatus::Returned)) {
            $order->transitionTo(OrderStatus::Returned, $actor);
        }

        if (in_array($shipment->status, ['picked_up', 'in_transit', 'out_for_delivery'], true)
            && $order->status === OrderStatus::Packed) {
            $order->transitionTo(OrderStatus::Shipped, $actor);
        }
    }
}
