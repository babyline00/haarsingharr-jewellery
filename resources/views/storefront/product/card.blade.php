{{-- Reusable responsive product card for editorial, listing, and related-product grids. --}}
@php
    $product ??= null;

    if (! $product) {
        return;
    }

    $image = $product->primaryImageCardPath();

    $sellableVariants = $product->variants
        ->filter(fn ($variant) => $variant->status === 'active')
        ->values();

    $variant = $sellableVariants->firstWhere('is_default', true)
        ?? $sellableVariants->first();

    $priceRange = $product->priceRange();
    $priceSpread = $sellableVariants->count() > 1
        && $priceRange[0] !== null
        && $priceRange[1] !== null
        && (float) $priceRange[0] !== (float) $priceRange[1];

    $price = $priceSpread ? (float) $priceRange[0] : $product->currentPrice();
    $regularPrice = (float) ($variant?->regular_price ?? $product->regular_price);
    $discount = $product->discountPercentage();

    // Single-option products can be added straight from the card; products
    // with a real choice get a "Choose options" shortcut to the detail page.
    $quickAdd = $variant
        && $sellableVariants->count() <= 1
        && $variant->inStock();
    $chooseOptions = $sellableVariants->count() > 1;
@endphp

<article
    class="group relative flex h-full min-w-0 flex-col overflow-hidden
           rounded-xl border border-champagne-light/60 bg-porcelain
           shadow-sm transition-all duration-300
           hover:border-champagne/70 hover:shadow-elegant
           sm:rounded-2xl md:hover:-translate-y-1"
>
    {{-- Product image --}}
    <div class="relative aspect-square shrink-0 overflow-hidden bg-ivory-alt">
        <a
            href="{{ route('products.show', $product) }}"
            class="absolute inset-0 block focus-visible:outline-none
                   focus-visible:ring-2 focus-visible:ring-inset
                   focus-visible:ring-champagne-dark"
            aria-label="View {{ $product->name }}"
        >
            @if ($image)
                <img
                    src="{{ \App\Support\Media::url($image) }}"
                    alt="{{ $product->name }}"
                    class="h-full w-full object-cover
                           transition-transform duration-500
                           motion-safe:group-hover:scale-[1.04]"
                    loading="lazy"
                    decoding="async"
                    onerror="this.onerror=null;this.src='{{ asset('images/placeholder-square.svg') }}'"
                >
            @else
                <img
                    src="{{ asset('images/placeholder-square.svg') }}"
                    alt=""
                    class="h-full w-full object-cover"
                    loading="lazy"
                    decoding="async"
                >
            @endif
        </a>

        {{-- Product badges --}}
        <div class="pointer-events-none absolute left-2 top-2 z-10 flex max-w-[75%] flex-wrap gap-1 sm:left-3 sm:top-3 sm:gap-1.5">
            @if ($product->is_new_arrival)
                <span class="badge badge-sm bg-porcelain/95 text-charcoal shadow-sm">
                    Just in
                </span>
            @endif

            @if ($product->is_best_seller)
                <span class="badge badge-sm bg-champagne text-charcoal shadow-sm">
                    Bestseller
                </span>
            @endif

            @if ($discount)
                <span class="badge badge-sm bg-rose text-white shadow-sm">
                    Save {{ $discount }}%
                </span>
            @endif
        </div>

        {{-- Wishlist --}}
        @if (config('app.features.wishlist'))
            <form
                action="{{ route('wishlist.add.public') }}"
                method="post"
                class="absolute right-2 top-2 z-10 sm:right-3 sm:top-3"
            >
                @csrf

                <input
                    type="hidden"
                    name="product_id"
                    value="{{ $product->id }}"
                >

                <button
                    type="submit"
                    aria-label="Add {{ $product->name }} to wishlist"
                    title="Add to wishlist"
                    class="flex h-10 w-10 items-center justify-center
                           rounded-full border border-champagne-light/40
                           bg-porcelain/95 text-charcoal shadow-sm
                           transition-colors duration-200
                           hover:bg-white hover:text-rose
                           focus-visible:outline-none focus-visible:ring-2
                           focus-visible:ring-champagne-dark
                           active:scale-95"
                >
                    <svg
                        width="18"
                        height="18"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.6"
                        aria-hidden="true"
                    >
                        <path d="M12 21s-7-4.5-9.5-9.5C.5 8 3 4 6.5 4 9 4 11 5.5 12 7c1-1.5 3-3 5.5-3 3.5 0 6 4 4 7.5C19 16.5 12 21 12 21z"/>
                    </svg>
                </button>
            </form>
        @endif

        {{-- Quick add to cart / choose options --}}
        @if ($quickAdd)
            <form
                action="{{ route('cart.items.store') }}"
                method="post"
                class="absolute inset-x-2 bottom-2 z-10
                       sm:inset-x-3 sm:bottom-3
                       md:translate-y-2 md:opacity-0
                       md:transition-all md:duration-200
                       md:group-hover:translate-y-0 md:group-hover:opacity-100
                       md:group-focus-within:translate-y-0
                       md:group-focus-within:opacity-100"
            >
                @csrf

                <input
                    type="hidden"
                    name="product_variant_id"
                    value="{{ $variant->id }}"
                >

                <input
                    type="hidden"
                    name="quantity"
                    value="1"
                >

                <button
                    type="submit"
                    class="btn-primary min-h-10 w-full px-2 py-2
                           text-xs shadow-md sm:min-h-11 sm:text-sm
                           focus-visible:outline-none focus-visible:ring-2
                           focus-visible:ring-champagne-dark
                           focus-visible:ring-offset-2"
                >
                    Add to bag
                </button>
            </form>
        @elseif ($chooseOptions)
            <a
                href="{{ route('products.show', $product) }}"
                class="absolute inset-x-2 bottom-2 z-10
                       rounded-lg bg-charcoal/90 px-3 py-2.5 text-center text-xs
                       font-medium text-white shadow-md backdrop-blur
                       transition-colors hover:bg-charcoal
                       focus-visible:outline-none focus-visible:ring-2
                       focus-visible:ring-champagne-dark focus-visible:ring-offset-2
                       sm:inset-x-3 sm:bottom-3 sm:min-h-11 sm:text-sm
                       md:translate-y-2 md:opacity-0
                       md:transition-all md:duration-200
                       md:group-hover:translate-y-0 md:group-hover:opacity-100
                       md:group-focus-within:translate-y-0
                       md:group-focus-within:opacity-100"
            >
                Choose options
            </a>
        @endif
    </div>

    {{-- Product information --}}
    <div class="flex flex-1 flex-col p-3 sm:p-3.5 md:p-4">
        <p
            class="mb-1.5 line-clamp-1 text-[9px] font-semibold uppercase
                   tracking-[0.14em] text-champagne-deep
                   sm:text-[10px] sm:tracking-[0.18em]"
        >
            {{ $product->metal ?? 'Fine jewellery' }}
        </p>

        <h3 class="min-h-[2.75rem] font-serif text-base leading-snug text-charcoal sm:text-lg md:text-xl">
            <a
                href="{{ route('products.show', $product) }}"
                class="line-clamp-2 rounded-sm transition-colors
                       hover:text-champagne-dark
                       focus-visible:outline-none focus-visible:ring-2
                       focus-visible:ring-champagne-dark"
            >
                {{ $product->name }}
            </a>
        </h3>

        {{-- Rating --}}
        @if ($product->rating_count > 0)
            <div
                class="mt-2 flex min-w-0 flex-wrap items-center gap-x-1
                       text-[11px] text-muted sm:text-xs"
                aria-label="Rated {{ number_format($product->rating_average, 1) }} out of 5"
            >
                <span class="text-champagne-deep" aria-hidden="true">★</span>

                <span class="font-medium text-charcoal">
                    {{ number_format($product->rating_average, 1) }}
                </span>

                <span>
                    ({{ $product->rating_count }})
                </span>
            </div>
        @endif

        {{-- Price --}}
        <div class="mt-auto flex flex-wrap items-baseline gap-x-2 gap-y-1 pt-3">
            @if ($priceSpread)
                <span class="text-[11px] text-muted">From</span>
            @endif

            <span class="font-serif text-lg text-charcoal sm:text-xl">
                Rs. {{ number_format($price) }}
            </span>

            @if ($priceSpread)
                <span class="font-serif text-sm text-muted sm:text-base">
                    – Rs. {{ number_format((float) $priceRange[1]) }}
                </span>
            @elseif ($discount && $regularPrice > $price)
                <span class="text-[11px] text-muted line-through sm:text-xs">
                    Rs. {{ number_format($regularPrice) }}
                </span>
            @endif
        </div>

        {{-- Stock status --}}
        @if (! $product->inStock())
            <p class="mt-1.5 text-xs text-muted">
                Currently unavailable
            </p>
        @endif
    </div>
</article>
