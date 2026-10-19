@extends('layouts.storefront')

@section('title', 'Order History — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-4xl">
            <div class="flex items-center justify-between mb-6">
                <h1 class="font-serif text-3xl">Your Orders</h1>
                <a href="{{ route('account.dashboard') }}" class="btn-ghost text-sm">Back to account</a>
            </div>

            @if ($orders->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($orders as $order)
                        <div class="card p-5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <a href="{{ route('account.orders.show', $order->number) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $order->number }}</a>
                                    <div class="text-xs text-muted">Placed {{ $order->created_at->format('j F Y, g:i A') }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-serif text-lg">Rs. {{ number_format($order->grand_total) }}</div>
                                    <span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-charcoal/5 flex items-center justify-between text-xs">
                                <div class="text-muted">{{ $order->items->count() }} item(s) • {{ $order->shippingAddress()?->city ?? '' }}</div>
                                <div class="flex gap-2">
                                    <a href="{{ route('account.orders.show', $order->number) }}" class="text-champagne-dark hover:underline">View details</a>
                                    @if ($order->isCancellable())
                                    <form action="{{ route('account.orders.cancel', $order) }}" method="post" onsubmit="return confirm('Cancel this order?')">
                                        @csrf
                                        <button class="text-danger hover:underline">Cancel</button>
                                    </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-8">{{ $orders->links() }}</div>
            @else
                <div class="text-center py-16">
                    <p class="font-serif text-xl text-muted">No orders yet</p>
                    <a href="{{ route('shop.index') }}" class="btn-gold mt-4">Browse the Collection</a>
                </div>
            @endif
        </div>
    </section>
@endsection
