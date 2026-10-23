<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\StockReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create an order from a cart. The cart has already been recalculated
     * server-side by the CheckoutService — the totals below are taken from
     * the server values, never from the form.
     */
    public function createFromCart(Cart $cart, array $data, ?string $idempotencyKey = null): Order
    {
        $user = $cart->user ?? (auth()->check() ? auth()->user() : null);

        $shippingAddress = $data['shipping_address'] ?? (
            $user ? $user->defaultShippingAddress() : null
        );

        $billingAddress = $data['billing_address'] ?? $shippingAddress;

        $order = new Order([
            'number'           => Order::nextOrderNumber(),
            'user_id'          => $user?->id,
            'guest_email'      => $user ? null : $data['email'],
            'guest_phone'      => $user?->phone ?: ($data['phone'] ?? null),
            'status'           => OrderStatus::Pending,
            'payment_status'   => PaymentStatus::Unpaid,
            'fulfilment_status' => 'unfulfilled',
            'currency'          => $cart->currency,
            'subtotal'          => $cart->subtotal,
            'discount_total'    => $cart->discount,
            'shipping_total'    => $cart->shipping,
            'tax_total'         => $cart->tax,
            'grand_total'        => $cart->total,
            'paid_amount'       => 0,
            'refunded_amount'   => 0,
            'coupon_id'          => $cart->coupon_id,
            'coupon_code'        => $cart->coupon_code,
            'coupon_discount'    => $cart->discount,
            'shipping_method'    => $data['shipping_method'] ?? null,
            'payment_method'     => $data['payment_method'] ?? null,
            'payment_provider'   => $data['payment_provider'] ?? null,
            'customer_notes'     => $data['notes'] ?? null,
            'locale'             => app()->getLocale(),
            'ip_address'         => request()?->ip(),
            'user_agent'         => request()?->userAgent(),
            'order_channel'      => 'web',
            'tracking_token'     => hash_hmac('sha256', $cart->uuid, config('app.key')),
        ]);

        // uuid is guarded from mass assignment, so the idempotency key must be
        // set explicitly before the HasUuid creating hook runs.
        $order->uuid = $idempotencyKey;
        $order->save();

        // Snapshot each item — including price, SKU and variant attributes so
        // historical orders stay correct after products change.
        foreach ($cart->items as $item) {
            $variant = $item->productVariant;
            $product = $item->product;

            OrderItem::create([
                'order_id'            => $order->id,
                'product_id'          => $product?->id,
                'product_variant_id'  => $variant?->id,
                'product_name'        => $product?->name ?? 'Unknown',
                'product_slug'        => $product?->slug,
                'sku'                 => $variant?->sku ?? $product?->sku,
                'variant_title'       => $variant?->formattedAttributes(),
                'variant_attributes'  => $variant?->attributes,
                'product_image'       => $product?->primaryImagePath(),
                'quantity'            => $item->quantity,
                'unit_price'          => $item->unit_price,
                'sale_price'          => $variant?->sale_price,
                'unit_cost'           => $variant?->cost_price ?? $product?->cost_price,
                'line_total'          => $item->line_total,
                'tax_amount'          => 0,
                'discount_amount'     => 0,
                'fulfilment_status'   => 'unfulfilled',
            ]);
        }

        return $order;
    }

    public function storeAddresses(Order $order, array $data): void
    {
        $shipping = $data['shipping_address'] ?? null;
        $billing  = $data['billing_address']  ?? $shipping;

        if ($shipping instanceof Address) {
            $this->createOrderAddress($order, 'shipping', $shipping);
        } elseif (is_array($shipping)) {
            OrderAddress::create(array_merge(['order_id' => $order->id, 'type' => 'shipping'], $shipping));
        }

        if ($billing instanceof Address) {
            $this->createOrderAddress($order, 'billing', $billing);
        } elseif (is_array($billing)) {
            OrderAddress::create(array_merge(['order_id' => $order->id, 'type' => 'billing'], $billing));
        }
    }

    private function createOrderAddress(Order $order, string $type, Address $address): OrderAddress
    {
        return OrderAddress::create([
            'order_id'        => $order->id,
            'type'            => $type,
            'first_name'      => $address->first_name,
            'last_name'       => $address->last_name,
            'phone_country'   => $address->phone_country,
            'phone'           => $address->phone,
            'address_line_1'  => $address->address_line_1,
            'address_line_2'  => $address->address_line_2,
            'landmark'        => $address->landmark,
            'city'            => $address->city,
            'province'        => $address->province,
            'country'         => $address->country,
            'postal_code'     => $address->postal_code,
        ]);
    }
}
