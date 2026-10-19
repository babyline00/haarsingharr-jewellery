{{-- Product card component — reusable across listings --}}
@php
    $product ??= null;
    if (! $product) return;
    $image = $product->primaryImageCardPath();
    $price = $product->currentPrice();
    $regularPrice = (float) $product->regular_price;
    $discount = $product->discountPercentage();
@endphp

<article class="card card-hover group relative flex flex-col">
    {{-- Image --}}
    <a href="{{ route('products.show', $product) }}" class="block relative aspect-square overflow-hidden bg-ivory-alt">
        @if ($image)
            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image) }}"
                 alt="{{ $product->name }}"
                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                 loading="lazy">
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-ivory-alt to-champagne/10 flex items-center justify-center text-muted/40 text-xs">Haarsingharr</div>
        @endif

        {{-- Badges --}}
        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
            @if ($product->is_new_arrival)
                <span class="badge bg-charcoal text-ivory">New</span>
            @endif
            @if ($product->is_best_seller)
                <span class="badge bg-champagne text-charcoal">Bestseller</span>
            @endif
            @if ($discount)
                <span class="badge bg-rose text-ivory">-{{ $discount }}%</span>
            @endif
        </div>

        {{-- Wishlist (Alpine hover) --}}
        @if (config('app.features.wishlist'))
            <form action="{{ route('wishlist.add.public') }}" method="post" class="absolute top-3 right-3">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="w-9 h-9 bg-porcelain/90 hover:bg-porcelain rounded-full flex items-center justify-center text-charcoal-soft hover:text-rose transition-all duration-200 shadow-sm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 21s-7-4.5-9.5-9.5C0.5 8 3 4 6.5 4 9 4 11 5.5 12 7c1-1.5 3-3 5.5-3C21 4 23.5 8 21.5 11.5 19 16.5 12 21 12 21z"/></svg>
                </button>
            </form>
        @endif

        {{-- Quick add --}}
        @if (! $product->has_variants && $product->inStock())
            <form action="{{ route('cart.items.store') }}" method="post" class="absolute bottom-3 left-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                @csrf
                <input type="hidden" name="product_variant_id" value="{{ $product->variants->where('is_default', true)->first()?->id ?? $product->variants->first()?->id }}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="btn-primary w-full text-xs py-2">Add to Cart</button>
            </form>
        @endif
    </a>

    {{-- Body --}}
    <div class="p-4 flex flex-col flex-1">
        <div class="text-[10px] uppercase tracking-widest text-champagne-dark font-medium mb-1">{{ $product->metal ?? 'Jewellery' }}</div>
        <h3 class="font-serif text-base leading-snug">
            <a href="{{ route('products.show', $product) }}" class="hover:text-champagne-dark transition-colors line-clamp-2">{{ $product->name }}</a>
        </h3>

        {{-- Rating --}}
        @if ($product->rating_count > 0)
            <div class="flex items-center gap-1 mt-1 text-xs text-muted">
                <span class="text-champagne">★</span>
                <span class="font-medium">{{ number_format($product->rating_average, 1) }}</span>
                <span>({{ $product->rating_count }})</span>
            </div>
        @endif

        {{-- Price --}}
        <div class="mt-2 flex items-baseline gap-2">
            @if ($discount)
                <span class="font-serif text-lg text-charcoal">Rs. {{ number_format($price) }}</span>
                <span class="text-xs text-muted line-through">Rs. {{ number_format($regularPrice) }}</span>
            @else
                <span class="font-serif text-lg text-charcoal">Rs. {{ number_format($price) }}</span>
            @endif
        </div>

        {{-- Stock hint --}}
        @if (! $product->inStock())
            <div class="text-xs text-muted mt-2 italic">Currently out of stock</div>
        @endif
    </div>
</article>
