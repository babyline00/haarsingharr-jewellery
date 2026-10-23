<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\ProductVariant;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly InventoryService $inventoryService,
        private readonly OrderService $orderService,
    ) {}

    /**
     * Create a customer order from the current cart. Server-side price
     * recompute, atomic stock reservation, idempotent submission.
     *
     * @param array $data  Form data — prices never trusted from the client.
     * @return Order
     */
    public function placeOrder(array $data): Order
    {
        // Idempotency must be resolved first: a retried submission arrives with an
        // already-cleared cart, so the empty-cart guard would otherwise reject the
        // duplicate instead of returning the original order.
        $idempotencyKey = $data['idempotency_key'] ?? session()->getId();
        $existingOrder = Order::where('uuid', $idempotencyKey)->first();
        if ($existingOrder) {
            return $existingOrder; // duplicate submission — return original
        }

        $cart = $this->cartService->current();
        if ($cart->isEmpty()) {
            throw new RuntimeException('Your cart is empty.');
        }

        return DB::transaction(function () use ($cart, $data, $idempotencyKey) {
            // 1. Recalculate the cart server-side (do NOT trust client totals).
            $cart->recalculate();

            // 2. Create the order with status=pending, payment_status=unpaid.
            $order = $this->orderService->createFromCart($cart, $data, $idempotencyKey);

            // 3. Reserve inventory for each variant atomically.
            foreach ($cart->items as $item) {
                $variant = $item->productVariant;
                if ($variant && $variant->hasStock($item->quantity)) {
                    $this->inventoryService->reserve($variant, $item->quantity, $order);
                } else if (! config('storefront.inventory.allow_backorders')) {
                    throw new RuntimeException(
                        "Variant {$variant?->sku} is out of stock."
                    );
                }
            }

            // 4. Save address snapshots.
            $this->orderService->storeAddresses($order, $data);

            // 5. Queue the order-placed notification (database queue).
            if ($user = $order->user) {
                Notification::send($user, new OrderPlacedNotification($order));
            }

            // 6. Clear the cart after successful checkout.
            $cart->items()->delete();
            $cart->delete();

            return $order->fresh();
        });
    }

    /**
     * Confirm payment and consume reservations → actual stock decrement.
     * Idempotent — repeated webhook events will not double-decrement.
     */
    public function confirmPayment(Order $order, ?Model $payment): void
    {
        DB::transaction(function () use ($order, $payment) {
            if ($order->payment_status->value === 'paid') {
                return; // already processed
            }

            $order->update([
                'payment_status' => 'paid',
                'paid_amount'    => $order->grand_total,
            ]);

            // Transition to confirmed if still pending.
            if ($order->status->value === 'pending') {
                $order->transitionTo(
                    \App\Enums\OrderStatus::Confirmed,
                    auth()->user(),
                );
            }

            // Consume each reservation atomically.
            foreach ($order->stockReservations()->active()->get() as $reservation) {
                $this->inventoryService->consume($reservation, $order);
            }
        });
    }

    /**
     * Cancel an order. Releases reserved stock if not yet consumed.
     */
    public function cancelOrder(Order $order, ?User $actor = null, ?string $reason = null): void
    {
        if (! $order->isCancellable()) {
            throw new RuntimeException("Order {$order->number} cannot be cancelled in its current state.");
        }

        DB::transaction(function () use ($order, $actor, $reason) {
            foreach ($order->stockReservations()->active()->get() as $reservation) {
                $this->inventoryService->release($reservation);
            }
            $order->transitionTo(
                \App\Enums\OrderStatus::Cancelled,
                $actor,
                $reason,
            );
        });
    }
}
