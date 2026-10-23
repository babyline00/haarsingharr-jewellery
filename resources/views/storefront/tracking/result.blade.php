@extends('layouts.storefront')

@section('title', 'Order ' . $order->number . ' — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-3xl">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
                <div>
                    <h1 class="font-serif text-3xl md:text-4xl">Order {{ $order->number }}</h1>
                    <p class="text-sm text-muted mt-1">Placed on {{ $order->created_at->format('j F Y, g:i A') }}</p>
                </div>
                <span class="badge badge-lg {{ $order->status->color() }}">{{ $order->status->label() }}</span>
            </div>

            {{-- Status timeline --}}
            <div class="mb-8">
                <div class="grid grid-cols-5 gap-2">
                    @php
                        $statuses = ['pending', 'confirmed', 'packed', 'shipped', 'delivered'];
                        $currentIdx = array_search($order->status->value, $statuses);
                    @endphp
                    @foreach ($statuses as $i => $status)
                        <div class="flex flex-col items-center text-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-medium
                                {{ $i <= ($currentIdx ?? -1) ? 'bg-champagne text-charcoal' : 'bg-ivory-alt text-muted' }}">
                                {{ $i + 1 }}
                            </div>
                            <div class="text-xs mt-1 capitalize {{ $i <= ($currentIdx ?? -1) ? 'text-charcoal' : 'text-muted' }}">
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Shipping info --}}
            @if ($order->tracking_number)
                <div class="card p-6 mb-6">
                    <h2 class="font-serif text-lg mb-2">Shipment</h2>
                    <p class="text-sm text-muted">Courier: <strong>{{ $order->courier }}</strong></p>
                    <p class="text-sm text-muted">Tracking number: <strong>{{ $order->tracking_number }}</strong></p>
                    @if ($order->trackUrl())
                        <a href="{{ $order->trackUrl() }}" target="_blank" class="btn-outline text-sm mt-3">Track on courier site →</a>
                    @endif
                </div>
            @endif

            {{-- Items --}}
            <div class="card overflow-hidden mb-6">
                <div class="p-5 border-b border-charcoal/5">
                    <h2 class="font-serif text-lg">Items in this order</h2>
                </div>
                <div class="divide-y divide-charcoal/5">
                    @foreach ($order->items as $item)
                        <div class="p-5 flex gap-4 items-center">
                            <div class="w-16 h-16 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                                @if ($item->product_image)
                                    <img src="{{ \App\Support\Media::url($item->product_image) }}" alt="" class="w-full h-full object-cover">
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate">{{ $item->product_name }}</div>
                                @if ($item->variant_title)
                                    <div class="text-xs text-muted">{{ $item->variant_title }}</div>
                                @endif
                                <div class="text-xs text-muted">Qty: {{ $item->quantity }} • Rs. {{ number_format($item->unit_price) }} each</div>
                            </div>
                            <div class="font-medium whitespace-nowrap">Rs. {{ number_format($item->line_total) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Status history --}}
            <div class="card p-6">
                <h2 class="font-serif text-lg mb-4">Order History</h2>
                <div class="space-y-3">
                    @foreach ($order->statusHistory as $history)
                        <div class="flex gap-3 text-sm">
                            <div class="w-2 h-2 rounded-full bg-champagne mt-1.5 flex-shrink-0"></div>
                            <div>
                                <div class="text-charcoal-soft">
                                    <span class="font-medium capitalize">{{ str_replace('_', ' ', $history->new_status) }}</span>
                                    @if ($history->actor)
                                        <span class="text-muted text-xs">by {{ $history->actor->name }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-muted">{{ $history->created_at->format('j F Y, g:i A') }}</div>
                                @if ($history->note)
                                    <p class="text-xs text-muted mt-1">{{ $history->note }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection