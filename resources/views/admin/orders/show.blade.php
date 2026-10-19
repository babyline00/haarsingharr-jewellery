@extends('admin.layout')

@section('title', 'Order ' . $order->number . ' — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">{{ $order->number }}</h1>
            <p class="text-sm text-muted mt-1">Placed {{ $order->created_at->format('j F Y, g:i A') }} • {{ $order->user?->name ?? $order->guest_email }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span>
            <span class="badge {{ $order->payment_status->color() }}">{{ $order->payment_status->label() }}</span>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">

        {{-- Items --}}
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Items</h2>
            </div>
            <div class="divide-y divide-charcoal/5">
                @foreach ($order->items as $item)
                    <div class="p-4 flex gap-3 items-center">
                        <div class="w-14 h-14 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                            @if ($item->product_image) <img src="{{ $item->product_image }}" alt="" class="w-full h-full object-cover"> @endif
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">{{ $item->product_name }}</div>
                            @if ($item->variant_title) <div class="text-xs text-muted">{{ $item->variant_title }}</div> @endif
                            <div class="text-xs text-muted">SKU: {{ $item->sku ?? '—' }} • Qty: {{ $item->quantity }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium text-sm">Rs. {{ number_format($item->line_total) }}</div>
                            <div class="text-xs text-muted">Rs. {{ number_format($item->unit_price) }} each</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-5 border-t border-charcoal/5 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-muted">Subtotal</span><span>Rs. {{ number_format($order->subtotal) }}</span></div>
                @if ($order->discount_total > 0)
                    <div class="flex justify-between text-success"><span class="text-muted">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span><span>-Rs. {{ number_format($order->discount_total) }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-muted">Shipping</span><span>Rs. {{ number_format($order->shipping_total) }}</span></div>
                <div class="flex justify-between font-serif text-lg pt-2 border-t border-charcoal/5"><span>Total</span><span>Rs. {{ number_format($order->grand_total) }}</span></div>
            </div>
        </div>

        {{-- Customer + status --}}
        <aside class="space-y-4">
            <div class="card p-5">
                <h3 class="font-serif text-base mb-3">Customer</h3>
                <div class="text-sm space-y-1">
                    <div class="font-medium">{{ $order->user?->name ?? 'Guest' }}</div>
                    <div class="text-muted">{{ $order->user?->email ?? $order->guest_email }}</div>
                    <div class="text-muted">{{ $order->guest_phone ?? $order->user?->phone }}</div>
                </div>
            </div>

            @if ($shipping = $order->shippingAddress())
            <div class="card p-5">
                <h3 class="font-serif text-base mb-3">Shipping Address</h3>
                <div class="text-sm text-muted">{{ $shipping->fullAddress() }}</div>
            </div>
            @endif

            <div class="card p-5">
                <h3 class="font-serif text-base mb-3">Update Status</h3>
                <form action="{{ route('admin.orders.status.update', $order) }}" method="post">
                    @csrf
                    @method('POST')
                    <select name="status" class="text-sm">
                        @foreach (\App\Enums\OrderStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($order->status->value === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <textarea name="note" rows="2" placeholder="Internal note" class="text-sm mt-2"></textarea>
                    <button type="submit" class="btn-gold w-full mt-2 text-sm">Update Status</button>
                </form>
            </div>

            <div class="card p-5">
                <h3 class="font-serif text-base mb-3">Shipment</h3>
                @if ($order->tracking_number)
                    <div class="text-sm space-y-1">
                        <div>Courier: <strong>{{ $order->courier }}</strong></div>
                        <div>Tracking: <strong>{{ $order->tracking_number }}</strong></div>
                        @if ($order->trackUrl())
                            <a href="{{ $order->trackUrl() }}" target="_blank" class="text-xs text-champagne-dark hover:underline">Track on courier site →</a>
                        @endif
                    </div>
                @else
                    <form action="{{ route('admin.orders.shipments.store', $order) }}" method="post">
                        @csrf
                        <label class="text-xs">Courier</label>
                        <select name="courier" class="text-sm">
                            <option value="tcs">TCS</option>
                            <option value="leopards">Leopards</option>
                            <option value="dhl">DHL</option>
                            <option value="fedex">FedEx</option>
                        </select>
                        <label class="text-xs mt-2 block">Tracking number</label>
                        <input type="text" name="tracking_number" required>
                        <label class="text-xs mt-2 block">Tracking URL (optional)</label>
                        <input type="url" name="tracking_url">
                        <button type="submit" class="btn-outline w-full text-sm mt-2">Mark Shipped</button>
                    </form>
                @endif
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn-outline text-sm flex-1">Invoice PDF</a>
                <a href="{{ route('admin.orders.packing_slip', $order) }}" target="_blank" class="btn-outline text-sm flex-1">Packing Slip</a>
            </div>
        </aside>
    </div>

    {{-- Status history --}}
    <div class="card p-5 mt-6">
        <h2 class="font-serif text-lg mb-4">Status History</h2>
        <div class="space-y-3">
            @foreach ($order->statusHistory as $history)
                <div class="flex gap-3 text-sm">
                    <div class="w-2 h-2 rounded-full bg-champagne mt-1.5 flex-shrink-0"></div>
                    <div>
                        <div class="text-charcoal-soft">
                            <span class="font-medium capitalize">{{ str_replace('_', ' ', $history->new_status) }}</span>
                            @if ($history->previous_status) <span class="text-muted text-xs">from {{ str_replace('_', ' ', $history->previous_status) }}</span> @endif
                            @if ($history->actor) <span class="text-muted text-xs">by {{ $history->actor->name }}</span> @endif
                        </div>
                        <div class="text-xs text-muted">{{ $history->created_at->format('j F Y, g:i A') }}</div>
                        @if ($history->note) <p class="text-xs text-muted mt-1">{{ $history->note }}</p> @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
