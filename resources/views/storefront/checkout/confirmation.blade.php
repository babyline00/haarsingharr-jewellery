@extends('layouts.storefront')

@section('title', 'Order Confirmation — Haarsingharr')

@section('content')
    <section class="py-16">
        <div class="container-haars max-w-3xl text-center">

            <div class="w-20 h-20 mx-auto rounded-full bg-success/10 flex items-center justify-center mb-6">
                <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-success"><polyline points="20 6 9 17 4 12"/></svg>
            </div>

            <h1 class="font-serif text-3xl md:text-4xl">Thank you for your order</h1>
            <p class="text-muted mt-3">A confirmation has been sent to <strong>{{ $order->user?->email ?? $order->guest_email }}</strong>.</p>

            <div class="mt-8 card p-8 text-left">
                <div class="flex items-center justify-between mb-4 pb-4 border-b border-charcoal/5">
                    <div>
                        <div class="text-xs uppercase tracking-widest text-muted">Order Number</div>
                        <div class="font-serif text-xl">{{ $order->number }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs uppercase tracking-widest text-muted">Total</div>
                        <div class="font-serif text-2xl">Rs. {{ number_format($order->grand_total) }}</div>
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="text-xs uppercase tracking-widest text-muted mb-1">Shipping To</div>
                        @if ($shipping = $order->shippingAddress())
                            <div class="text-charcoal-soft">{{ $shipping->first_name }} {{ $shipping->last_name }}</div>
                            <div class="text-muted text-xs mt-1">{{ $shipping->fullAddress() }}</div>
                            <div class="text-muted text-xs">{{ $shipping->phone_country }} {{ $shipping->phone }}</div>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-widest text-muted mb-1">Payment</div>
                        <div class="text-charcoal-soft">{{ ucfirst($order->payment_method) }}</div>
                        <div class="text-xs text-muted mt-1">Status: <span class="font-medium text-charcoal-soft">{{ ucfirst($order->payment_status->value) }}</span></div>
                    </div>
                </div>

                <div class="mt-6 pt-6 border-t border-charcoal/5">
                    <div class="text-xs uppercase tracking-widest text-muted mb-3">Items</div>
                    @foreach ($order->items as $item)
                        <div class="flex justify-between items-center py-2 border-b border-charcoal/5 last:border-0">
                            <div class="flex items-center gap-3">
                                @if ($item->product_image)
                                    <img src="{{ $item->product_image }}" alt="" class="w-12 h-12 object-cover rounded">
                                @endif
                                <div>
                                    <div class="text-sm font-medium">{{ $item->product_name }}</div>
                                    <div class="text-xs text-muted">Qty: {{ $item->quantity }}</div>
                                </div>
                            </div>
                            <div class="text-sm font-medium">Rs. {{ number_format($item->line_total) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                @if ($order->user)
                    <a href="{{ route('account.orders.show', $order->number) }}" class="btn-gold">View Order</a>
                @else
                    <a href="{{ route('order.tracking.form') }}" class="btn-gold">Track Order</a>
                @endif
                <a href="{{ route('shop.index') }}" class="btn-outline">Continue Shopping</a>
            </div>

            <p class="text-xs text-muted mt-8">
                For order enquiries, please contact us at <a href="mailto:{{ config('storefront.contact.email') }}" class="underline hover:text-champagne">{{ config('storefront.contact.email') }}</a>
                or WhatsApp <a href="https://wa.me/{{ preg_replace('/\D/', '', config('storefront.notifications.whatsapp_number')) }}" target="_blank" class="underline hover:text-champagne">{{ config('storefront.notifications.whatsapp_number') }}</a>.
            </p>
        </div>
    </section>
@endsection
