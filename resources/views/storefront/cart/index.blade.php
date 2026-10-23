@extends('layouts.storefront')

@section('title', 'Your Cart — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars">
            <h1 class="font-serif text-3xl md:text-4xl mb-8">Your Shopping Cart</h1>

            @if ($cart->isEmpty())
                <div class="text-center py-16 md:py-20">
                    <svg class="w-16 h-16 mx-auto text-muted/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8-4v10l-8 4m0-10L4 7m8-4v10l-8 4m0-10L4 7m8-4v10M4 7v10l8 4"/></svg>
                    <p class="font-serif text-2xl md:text-3xl text-muted mb-3">Your cart is currently empty</p>
                    <p class="text-sm text-muted mb-6 max-w-xs mx-auto">Discover our hand-crafted pieces — each designed to become an heirloom.</p>
                    <a href="{{ route('shop.index') }}" class="btn-gold mt-6">Explore the Collection</a>
                </div>
            @else
                <div class="grid lg:grid-cols-[1fr_380px] gap-8 lg:gap-12">

                    {{-- Items --}}
                    <div class="space-y-4">
                        @foreach ($cart->items as $item)
                            @php
                                $product = $item->product;
                                $variant = $item->productVariant;
                                $image = $product?->primaryImageCardPath();
                            @endphp
                            <div class="card p-4 md:p-5 flex gap-4">
                                <a href="{{ route('products.show', $product) }}" class="block w-24 h-24 md:w-28 md:h-28 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                                    @if ($image)
                                        <img src="{{ \App\Support\Media::url($image) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @endif
                                </a>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between gap-4">
                                        <div>
                                            <a href="{{ route('products.show', $product) }}" class="font-serif text-base hover:text-champagne-dark transition-colors line-clamp-2">{{ $product->name }}</a>
                                            @if ($variant?->formattedAttributes())
                                                <p class="text-xs text-muted mt-0.5">{{ $variant->formattedAttributes() }}</p>
                                            @endif
                                            <p class="text-xs text-muted mt-1">SKU: {{ $variant?->sku ?? $product?->sku }}</p>
                                        </div>
                                        <form action="{{ route('cart.items.destroy', $item->id) }}" method="post" onsubmit="return confirm('Remove this item?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-muted hover:text-danger transition-colors text-sm flex-shrink-0" aria-label="Remove {{ $product->name }} from cart">×</button>
                                        </form>
                                    </div>

                                    <div class="flex flex-col md:flex-row md:items-end md:justify-between mt-3 gap-4">
                                        <form action="{{ route('cart.items.update', $item->id) }}" method="post" class="inline-flex items-center border border-charcoal/10 rounded-lg">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" onclick="this.nextElementSibling.stepDown(); this.form.submit()" class="px-3 py-2 text-charcoal-soft hover:text-charcoal hover:bg-ivory-alt/50 transition-colors" aria-label="Decrease quantity">−</button>
                                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" max="20" class="w-12 text-center border-0 focus:ring-0 text-sm" style="appearance: none;">
                                            <button type="button" onclick="this.previousElementSibling.stepUp(); this.form.submit()" class="px-3 py-2 text-charcoal-soft hover:text-charcoal hover:bg-ivory-alt/50 transition-colors" aria-label="Increase quantity">+</button>
                                        </form>
                                        <div class="text-right md:text-right flex-1 md:flex md:items-end md:justify-end">
                                            <div class="font-serif text-lg">Rs. {{ number_format($item->line_total) }}</div>
                                            @if ($item->unit_price != $product?->currentPrice())
                                                <div class="text-xs text-muted">Price updated to Rs. {{ number_format($product?->currentPrice()) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="flex justify-between items-center pt-4">
                            <a href="{{ route('shop.index') }}" class="text-sm text-charcoal-soft hover:text-champagne transition-colors flex items-center gap-1">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                                Continue shopping
                            </a>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <aside class="lg:sticky lg:top-24 self-start">
                        <div class="card p-6">
                            <h2 class="font-serif text-xl mb-4">Order Summary</h2>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted">Subtotal ({{ $cart->itemCount() }} items)</span>
                                    <span class="font-medium">Rs. {{ number_format($cart->subtotal) }}</span>
                                </div>

                                @if ($cart->discount > 0)
                                    <div class="flex justify-between text-success">
                                        <span class="text-muted">Discount {{ $cart->coupon_code ? '(' . $cart->coupon_code . ')' : '' }}</span>
                                        <span>-Rs. {{ number_format($cart->discount) }}</span>
                                    </div>
                                @endif

                                <div class="flex justify-between">
                                    <span class="text-muted">Shipping</span>
                                    <span class="font-medium">{{ $cart->shipping > 0 ? 'Rs. ' . number_format($cart->shipping) : 'Free' }}</span>
                                </div>

                                @if ($cart->tax > 0)
                                    <div class="flex justify-between">
                                        <span class="text-muted">Tax</span>
                                        <span class="font-medium">Rs. {{ number_format($cart->tax) }}</span>
                                    </div>
                                @endif

                                <div class="border-t border-charcoal/10 pt-3 mt-3 flex justify-between items-baseline">
                                    <span class="font-serif text-lg">Total</span>
                                    <span class="font-serif text-2xl">Rs. {{ number_format($cart->total) }}</span>
                                </div>
                            </div>

                            {{-- Coupon --}}
                            @if (! $cart->coupon_id)
                                <form action="{{ route('cart.coupon.apply') }}" method="post" class="mt-4">
                                    @csrf
                                    <label class="text-xs font-medium uppercase tracking-widest text-charcoal-soft">Promo Code</label>
                                    <div class="flex gap-2 mt-1">
                                        <input type="text" name="code" placeholder="HAARS10" class="text-sm flex-1">
                                        <button type="submit" class="btn-outline text-sm shrink-0">Apply</button>
                                    </div>
                                </form>
                            @else
                                <form action="{{ route('cart.coupon.remove') }}" method="post" class="mt-4">
                                    @csrf
                                    @method('DELETE')
                                    <div class="flex items-center justify-between bg-success/10 px-3 py-2 rounded-md text-sm">
                                        <span class="text-success">Coupon {{ $cart->coupon_code }} applied</span>
                                        <button class="text-danger hover:underline">Remove</button>
                                    </div>
                                </form>
                            @endif

                            <a href="{{ route('checkout.show') }}" class="btn-gold w-full mt-6">Proceed to Checkout</a>

                            <div class="mt-4 text-xs text-muted text-center">
                                <p>Secure checkout — SSL encrypted.</p>
                                <p>Cash on Delivery available nationwide.</p>
                            </div>
                        </div>
                    </aside>
                </div>
            @endif
        </div>
    </section>
@endsection