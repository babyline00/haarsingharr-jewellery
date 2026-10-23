@extends('layouts.storefront')

@section('title', 'Order ' . $order->number . ' — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-3xl">
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-serif text-2xl md:text-3xl">Order {{ $order->number }}</h1>
                <a href="{{ route('account.orders.index') }}" class="btn-ghost text-sm">Back to orders</a>
            </div>

            <div class="card p-6 mb-6 flex items-center justify-between">
                <div>
                    <div class="text-xs uppercase tracking-widest text-muted">Status</div>
                    <span class="badge {{ $order->status->color() }} mt-1">{{ $order->status->label() }}</span>
                </div>
                <div class="text-right">
                    <div class="text-xs uppercase tracking-widest text-muted">Total</div>
                    <div class="font-serif text-2xl">Rs. {{ number_format($order->grand_total) }}</div>
                </div>
            </div>

            @if ($order->tracking_number)
                <div class="card p-6 mb-6">
                    <h2 class="font-serif text-lg mb-3">Tracking</h2>
                    <div class="text-sm space-y-1">
                        <div class="text-muted">Courier: <strong class="text-charcoal">{{ ucfirst($order->courier) }}</strong></div>
                        <div class="text-muted">Tracking number: <strong class="text-charcoal">{{ $order->tracking_number }}</strong></div>
                        @if ($order->trackUrl())
                            <a href="{{ $order->trackUrl() }}" target="_blank" class="text-champagne-dark hover:underline mt-2 inline-block">Track on courier site →</a>
                        @endif
                    </div>
                </div>
            @endif

            <div class="card overflow-hidden mb-6">
                <div class="p-5 border-b border-charcoal/5"><h2 class="font-serif text-lg">Items</h2></div>
                <div class="divide-y divide-charcoal/5">
                    @foreach ($order->items as $item)
                        <div class="p-5 flex gap-4">
                            <div class="w-16 h-16 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                                @if ($item->product_image) <img src="{{ \App\Support\Media::url($item->product_image) }}" alt="" class="w-full h-full object-cover"> @endif
                            </div>
                            <div class="flex-1">
                                <div class="font-medium">{{ $item->product_name }}</div>
                                @if ($item->variant_title) <div class="text-xs text-muted">{{ $item->variant_title }}</div> @endif
                                <div class="text-xs text-muted">Qty: {{ $item->quantity }}</div>
                            </div>
                            <div class="text-right font-medium">Rs. {{ number_format($item->line_total) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="p-5 border-t border-charcoal/5 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-muted">Subtotal</span><span>Rs. {{ number_format($order->subtotal) }}</span></div>
                    @if ($order->discount_total > 0)
                        <div class="flex justify-between text-success"><span class="text-muted">Discount</span><span>-Rs. {{ number_format($order->discount_total) }}</span></div>
                    @endif
                    <div class="flex justify-between"><span class="text-muted">Shipping</span><span>Rs. {{ number_format($order->shipping_total) }}</span></div>
                    <div class="flex justify-between font-serif text-lg pt-2 border-t border-charcoal/5"><span>Total</span><span>Rs. {{ number_format($order->grand_total) }}</span></div>
                </div>
            </div>

            @if ($shipping = $order->shippingAddress())
                <div class="card p-6 mb-6">
                    <h2 class="font-serif text-lg mb-3">Shipping Address</h2>
                    <div class="text-sm text-muted">{{ $shipping->first_name }} {{ $shipping->last_name }}</div>
                    <div class="text-sm text-muted">{{ $shipping->fullAddress() }}</div>
                    <div class="text-sm text-muted">{{ $shipping->phone_country }} {{ $shipping->phone }}</div>
                </div>
            @endif

            {{-- Status history timeline --}}
            <div class="card p-6">
                <h2 class="font-serif text-lg mb-4">Order History</h2>
                <div class="space-y-3">
                    @foreach ($order->statusHistory as $history)
                        <div class="flex gap-3 text-sm">
                            <div class="w-2 h-2 rounded-full bg-champagne mt-1.5 flex-shrink-0"></div>
                            <div>
                                <div class="text-charcoal-soft">
                                    <span class="font-medium capitalize">{{ str_replace('_', ' ', $history->new_status) }}</span>
                                </div>
                                <div class="text-xs text-muted">{{ $history->created_at->format('j F Y, g:i A') }}</div>
                                @if ($history->note) <p class="text-xs text-muted mt-1">{{ $history->note }}</p> @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
