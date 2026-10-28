blade
@extends('layouts.storefront')

@section('title', ($product->seo_title ?: $product->name) . ' — Haarsingharr')

@section('meta_description', \Illuminate\Support\Str::limit(
    strip_tags($product->seo_description ?: ($product->short_description ?? $product->description ?? $product->name)),
    160
))

@section('structured_data')
<<<<<<< HEAD
    @php
        $schemaImage = $product->primaryImagePath();
        [$schemaLowPrice, $schemaHighPrice] = $product->priceRange();
        $schemaOffers = $product->variants
            ->filter(fn ($variant) => $variant->status === 'active')
            ->values();
        $schemaPrice = $selectedVariant?->currentPrice() ?? $product->currentPrice();

        if ($schemaOffers->isNotEmpty()) {
            $schemaMinPrice = $schemaOffers->min(fn ($variant) => $variant->currentPrice());
            $schemaMaxPrice = $schemaOffers->max(fn ($variant) => $variant->currentPrice());
        } else {
            $schemaMinPrice = $schemaMaxPrice = $schemaPrice;
=======
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org/",
        "@type": "Product",
        "name": {{ json_encode($product->name) }},
        "image": [{{ json_encode(\App\Support\Media::url($product->primaryImagePath())) }}],
        "description": {{ json_encode(strip_tags($product->short_description ?? $product->description)) }},
        "sku": {{ json_encode($product->sku) }},
        "brand": { "@type": "Brand", "name": "Haarsingharr" },
        @if ($product->rating_count > 0)
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": {{ $product->rating_average }},
            "reviewCount": {{ $product->rating_count }}
        },
        @endif
        "offers": {
            "@type": "Offer",
            "url": "{{ route('products.show', $product) }}",
            "priceCurrency": "PKR",
            "price": {{ (float) $product->currentPrice() }},
            "availability": "{{ $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock' }}"
>>>>>>> origin/main
        }
    @endphp

    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'image' => $schemaImage
                ? [\App\Support\Media::url($schemaImage)]
                : [asset('images/placeholder-square.svg')],
            'description' => strip_tags($product->short_description ?? $product->description ?? $product->name),
            'sku' => $product->sku,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'Haarsingharr',
            ],
            ...($product->rating_count > 0 ? [
                'aggregateRating' => [
                    '@type' => 'AggregateRating',
                    'ratingValue' => (float) $product->rating_average,
                    'reviewCount' => (int) $product->rating_count,
                ],
            ] : []),
            'offers' => $schemaOffers->count() > 1 ? [
                '@type' => 'AggregateOffer',
                'url' => route('products.show', $product),
                'priceCurrency' => 'PKR',
                'lowPrice' => (float) $schemaMinPrice,
                'highPrice' => (float) $schemaMaxPrice,
                'offerCount' => $schemaOffers->count(),
                'offers' => $schemaOffers->map(fn ($variant) => [
                    '@type' => 'Offer',
                    'sku' => $variant->sku ?? $variant->id,
                    'name' => $variant->displayLabel(),
                    'price' => (float) $variant->currentPrice(),
                    'priceCurrency' => 'PKR',
                    'availability' => $variant->inStock()
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock',
                ])->values()->all(),
            ] : [
                '@type' => 'Offer',
                'url' => route('products.show', $product),
                'priceCurrency' => 'PKR',
                'price' => (float) $schemaPrice,
                'availability' => $product->inStock()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
        ], fn ($value) => $value !== null), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
    </script>
@endsection

@section('content')
    @php
        $sellableVariants = $product->variants
            ->filter(fn ($variant) => $variant->status === 'active')
            ->values();

        // Show the option picker only when the customer has a real choice.
        $variantPicker = $product->has_variants && $sellableVariants->count() > 1;

        $selectedVariant = $sellableVariants->firstWhere('is_default', true)
            ?? $sellableVariants->first(fn ($variant) => $variant->inStock())
            ?? $sellableVariants->first();

        $price = $product->currentPrice();
        $discount = $product->discountPercentage();
        $images = $product->images;

        $pickImagePath = function (...$paths) {
            foreach ($paths as $path) {
                if (blank($path)) {
                    continue;
                }

                if (\Illuminate\Support\Str::startsWith($path, [
                    'https://',
                    'http://',
                    '/images/',
                    '/storage/',
                ])) {
                    return $path;
                }

                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    return $path;
                }
            }

            return collect($paths)->first(fn ($path) => filled($path));
        };

        $toGalleryImage = function ($image, ?int $variantId, string $altSuffix = '') use ($pickImagePath, $product) {
            return [
                'full' => \App\Support\Media::url(
                    $pickImagePath($image->gallery_path, $image->path)
                ),
                'thumb' => \App\Support\Media::url(
                    $pickImagePath(
                        $image->thumbnail_path,
                        $image->card_path,
                        $image->path
                    )
                ),
                'alt' => ($image->alt_text ?: $product->name).$altSuffix,
                'variantId' => $variantId,
            ];
        };

        $galleryImages = $images
            ->map(fn ($image) => $toGalleryImage($image, null))
            ->filter(fn ($image) => filled($image['full']))
            ->values();

        // Variant-specific photos join the gallery so selecting an option can
        // switch the main image to the photo of that exact variant.
        $variantImageIndex = [];
        $sellableVariants
            ->filter(fn ($variant) => $variant->images->isNotEmpty())
            ->each(function ($variant) use ($toGalleryImage, &$galleryImages, &$variantImageIndex) {
                $variant->images->each(function ($image, $index) use ($toGalleryImage, $variant, &$galleryImages, &$variantImageIndex) {
                    $entry = $toGalleryImage(
                        $image,
                        (int) $variant->id,
                        $index === 0 ? ' — '.$variant->displayLabel() : ''
                    );

                    if (blank($entry['full'])) {
                        return;
                    }

                    $variantImageIndex[$variant->id] ??= $galleryImages->count();
                    $galleryImages->push($entry);
                });
            });

        if ($galleryImages->isEmpty()) {
            $galleryImages = collect([[
                'full' => asset('images/placeholder-square.svg'),
                'thumb' => asset('images/placeholder-square.svg'),
                'alt' => $product->name,
                'variantId' => null,
            ]]);
        }

        $imageUrls = $galleryImages->pluck('full')->values();

        // Everything Alpine needs to drive the option picker in one payload.
        $variantPayload = $sellableVariants->mapWithKeys(fn ($variant) => [
            (string) $variant->id => [
                'id'           => (int) $variant->id,
                'label'        => $variant->displayLabel(),
                'price'        => (float) $variant->currentPrice(),
                'regular'      => (float) $variant->regular_price,
                'onSale'       => $variant->isOnSale(),
                'savePct'      => $variant->savingsPercentage(),
                'stock'        => $variant->availableStock(),
                'lowThreshold' => (int) ($variant->low_stock_threshold ?? 5),
                'backorder'    => $variant->backorderable(),
                'sku'          => $variant->sku,
                'image'        => $variantImageIndex[$variant->id] ?? null,
                'attributes'   => collect($product->variantAttributeNames())
                    ->mapWithKeys(fn ($name) => [$name => $variant->attributeValue($name)])
                    ->all(),
            ],
        ])->all();

        $optionGroups = $product->variantAttributeNames()
            ->mapWithKeys(function ($name) use ($sellableVariants) {
                $values = $sellableVariants
                    ->map(fn ($variant) => $variant->attributeValue($name))
                    ->filter()
                    ->unique()
                    ->values();

                return [$name => $values];
            })
            ->filter(fn ($values) => $values->isNotEmpty());

        $initialSelection = collect($optionGroups->keys())
            ->mapWithKeys(fn ($name) => [$name => $selectedVariant?->attributeValue($name)])
            ->filter()
            ->all();
    @endphp

    <main
        class="min-h-screen bg-ivory pb-24 text-charcoal lg:pb-10"
        x-data="variantPicker({{ \Illuminate\Support\Js::from([
            'initialVariantId' => $selectedVariant?->id,
            'initialSelection' => (object) $initialSelection,
            'variants'         => (object) $variantPayload,
            'groups'           => $optionGroups
                ->map(fn ($values, $name) => ['name' => $name, 'values' => $values->all()])
                ->values()
                ->all(),
            'maxQuantity'      => (int) config('storefront.cart.max_qty_per_item', 20),
            'imageUrls'        => $imageUrls->all(),
            'fallbackPrice'    => (float) ($selectedVariant?->currentPrice() ?? $price),
        ]) }}"
    >
        <div class="container-haars mx-auto max-w-7xl px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8">

            {{-- Breadcrumbs --}}
<<<<<<< HEAD
            <nav
                class="mb-5 flex flex-wrap items-center gap-2 text-xs text-muted sm:mb-7"
                aria-label="Breadcrumb"
            >
                <a href="{{ route('home') }}" class="hover:text-champagne-dark">
                    Home
                </a>

                <span aria-hidden="true">/</span>

                <a href="{{ route('shop.index') }}" class="hover:text-champagne-dark">
                    Shop
                </a>

=======
            <nav class="text-xs text-muted mb-6" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-champagne transition-colors">Home</a>
                <span class="mx-1.5">/</span>
                <a href="{{ route('shop.index') }}" class="hover:text-champagne transition-colors">Shop</a>
>>>>>>> origin/main
                @if ($product->category)
                    <span aria-hidden="true">/</span>

                    <a
                        href="{{ route('categories.show', $product->category) }}"
                        class="hover:text-champagne-dark"
                    >
                        {{ $product->category->name }}
                    </a>
                @endif

                <span aria-hidden="true">/</span>

                <span class="max-w-[180px] truncate text-charcoal sm:max-w-xs">
                    {{ $product->name }}
                </span>
            </nav>

            {{-- Product detail --}}
            <section class="grid grid-cols-1 items-start gap-7 lg:grid-cols-2 lg:gap-12 xl:gap-16">

<<<<<<< HEAD
                {{-- Product gallery --}}
                <div class="min-w-0">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[76px_minmax(0,1fr)] sm:gap-4">

                        {{-- Desktop thumbnails --}}
                        @if ($galleryImages->count() > 1)
                            <div class="order-2 hidden max-h-[680px] flex-col gap-3 overflow-y-auto sm:order-1 sm:flex">
                                @foreach ($galleryImages as $index => $galleryImage)
                                    <button
                                        type="button"
                                        @click="activeImage = {{ $index }}"
                                        :aria-pressed="activeImage === {{ $index }}"
                                        :class="activeImage === {{ $index }}
                                            ? 'border-champagne-dark opacity-100'
                                            : 'border-transparent opacity-75 hover:opacity-100'"
                                        class="aspect-square w-full shrink-0 overflow-hidden rounded-lg border-2 bg-ivory-alt transition"
                                        aria-label="Show product image {{ $index + 1 }}"
                                    >
                                        <img
                                            src="{{ $galleryImage['thumb'] }}"
                                            alt="{{ $galleryImage['alt'] }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                            onerror="this.onerror=null;this.src='{{ asset('images/placeholder-square.svg') }}'"
                                        >
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Main image --}}
                        <div class="relative order-1 min-w-0 overflow-hidden rounded-xl bg-ivory-alt ring-1 ring-charcoal/5 sm:order-2 sm:rounded-2xl">
                            <div class="aspect-square">
                                @foreach ($galleryImages as $index => $galleryImage)
                                    <img
                                        x-show="activeImage === {{ $index }}"
                                        x-cloak
                                        x-transition.opacity.duration.250ms
                                        src="{{ $galleryImage['full'] }}"
                                        alt="{{ $galleryImage['alt'] }}"
                                        class="absolute inset-0 h-full w-full object-cover"
                                        @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif
                                        onerror="this.onerror=null;this.src='{{ asset('images/placeholder-square.svg') }}'"
                                    >
                                @endforeach
                            </div>

                            @if ($discount)
                                <span class="absolute left-3 top-3 rounded-full bg-danger px-3 py-1.5 text-xs font-semibold text-white sm:left-4 sm:top-4">
                                    Save {{ $discount }}%
                                </span>
                            @endif

                            @if (config('app.features.wishlist'))
                                <form
                                    action="{{ route('wishlist.add.public') }}"
                                    method="post"
                                    class="absolute right-3 top-3 sm:right-4 sm:top-4"
                                >
                                    @csrf

                                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                                    <button
                                        type="submit"
                                        aria-label="Add {{ $product->name }} to wishlist"
                                        class="flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-xl shadow-md transition hover:text-rose focus-visible:outline focus-visible:outline-2 focus-visible:outline-champagne-dark"
                                    >
                                        ♡
                                    </button>
                                </form>
                            @endif

                            @if ($galleryImages->count() > 1)
                                <button
                                    type="button"
                                    @click="changeImage(-1)"
                                    class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-xl shadow-md sm:h-10 sm:w-10"
                                    aria-label="Previous image"
                                >
                                    ‹
                                </button>

                                <button
                                    type="button"
                                    @click="changeImage(1)"
                                    class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/95 text-xl shadow-md sm:h-10 sm:w-10"
                                    aria-label="Next image"
                                >
                                    ›
                                </button>

                                <span class="absolute bottom-3 right-3 rounded-full bg-white/90 px-3 py-1 text-xs text-charcoal sm:hidden">
                                    <span x-text="activeImage + 1"></span>
                                    / {{ $galleryImages->count() }}
                                </span>
                            @endif
                        </div>

                        {{-- Mobile thumbnail strip --}}
                        @if ($galleryImages->count() > 1)
                            <div class="order-3 flex gap-2 overflow-x-auto pb-1 sm:hidden">
                                @foreach ($galleryImages as $index => $galleryImage)
                                    <button
                                        type="button"
                                        @click="activeImage = {{ $index }}"
                                        :class="activeImage === {{ $index }}
                                            ? 'border-champagne-dark'
                                            : 'border-transparent'"
                                        class="h-16 w-16 shrink-0 overflow-hidden rounded-md border-2 bg-ivory-alt"
                                        aria-label="Show product image {{ $index + 1 }}"
                                    >
                                        <img
                                            src="{{ $galleryImage['thumb'] }}"
                                            alt="{{ $galleryImage['alt'] }}"
                                            class="h-full w-full object-cover"
                                            loading="lazy"
                                        >
                                    </button>
                                @endforeach
                            </div>
                        @endif
=======
                {{-- Gallery --}}
                @php
                    $images = $product->images;
                    $imageUrls = $images->map(fn ($i) => \App\Support\Media::url($i->path))->values();
                    if ($imageUrls->isEmpty()) {
                        $imageUrls = collect([\App\Support\Media::url(null)]);
                    }
                @endphp
                <div x-data="{ active: 0, urls: {{ \Illuminate\Support\Js::from($imageUrls) }} }" class="space-y-4">
                    <div class="aspect-square relative bg-ivory-alt rounded-2xl overflow-hidden">
                        <img :src="urls[active]"
                             src="{{ $imageUrls->first() }}"
                             alt="{{ $images->first()->alt_text ?? $product->name }}"
                             class="w-full h-full object-cover cursor-zoom-in">
>>>>>>> origin/main
                    </div>
                    @if ($imageUrls->count() > 1)
                        <div class="grid grid-cols-5 gap-2">
                            @foreach ($imageUrls as $index => $url)
                                <button type="button" @click="active = {{ $index }}"
                                        class="aspect-square rounded-lg overflow-hidden border-2"
                                        :class="active === {{ $index }} ? 'border-champagne' : 'border-transparent'"
                                        aria-label="View image {{ $loop->iteration }} of {{ $imageUrls->count() }}"
                                        :aria-selected="active === {{ $index }}">
                                    <img src="{{ $url }}" alt="{{ $images[$index]->alt_text ?? '' }}" class="w-full h-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

<<<<<<< HEAD
                {{-- Purchase information --}}
                <div class="flex min-w-0 flex-col lg:sticky lg:top-24">

                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-[0.22em] text-champagne-deep sm:text-xs">
                        {{ $product->category?->name ?? $product->metal ?? 'Fine Jewellery' }}
                    </p>

                    <h1 class="font-serif text-2xl leading-tight sm:text-3xl xl:text-4xl">
                        {{ $product->name }}
                    </h1>

                    @if ($product->rating_count > 0)
                        <a
                            href="#reviews"
                            @click.prevent="tab = 'reviews'; $nextTick(() => document.getElementById('reviews')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                            class="mt-3 flex flex-wrap items-center gap-2 text-sm"
                        >
                            <span class="tracking-wider text-warning" aria-label="Star rating">★★★★★</span>

                            <span class="font-medium">
                                {{ number_format($product->rating_average, 1) }}
                            </span>

                            <span class="text-muted">
                                ({{ $product->rating_count }} reviews)
                            </span>
                        </a>
                    @endif

                    {{-- Price --}}
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span
                            class="font-serif text-2xl text-charcoal sm:text-3xl"
                            x-text="formattedPrice"
                        >
                            Rs. {{ number_format($selectedVariant?->currentPrice() ?? $price) }}
                        </span>

                        <span class="flex items-center gap-3" x-show="currentVariant?.onSale" x-cloak>
                            <span
                                class="text-sm text-muted line-through"
                                x-text="formatMoney(currentVariant?.regular ?? 0)"
                            ></span>

                            <span
                                class="rounded-full bg-danger px-3 py-1 text-xs font-semibold text-white"
                                x-text="(currentVariant?.savePct ?? 0) + '% OFF'"
                            ></span>
                        </span>
                    </div>

                    {{-- Stock status --}}
                    <div class="mt-3 flex flex-wrap items-center gap-3">
                        <span
                            class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-xs"
                            :class="stockBadgeClass"
                        >
                            <span>●</span>
                            <span x-text="stockLabel">{{ $selectedVariant?->inStock() ? 'In stock and ready to ship' : 'Currently unavailable' }}</span>
                        </span>

                        <span
                            class="text-xs text-muted"
                            x-show="currentVariant?.sku"
                            x-text="'SKU: ' + (currentVariant?.sku || '')"
                            x-cloak
                        >
                            @if ($selectedVariant?->sku) SKU: {{ $selectedVariant->sku }} @endif
                        </span>
=======
                @if ($product->videos->isNotEmpty())
                    <div class="card p-4">
                        <h2 class="font-serif text-lg mb-3">Videos &amp; Reels</h2>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($product->videos as $video)
                                <div class="rounded-xl overflow-hidden bg-charcoal">
                                    <video src="{{ $video->url() }}"
                                           @if ($video->thumbnailUrl()) poster="{{ $video->thumbnailUrl() }}" @endif
                                           controls playsinline preload="metadata"
                                           class="w-full aspect-[9/16] object-cover bg-black"></video>
                                    @if ($video->title)
                                        <p class="text-xs text-ivory/80 p-2">{{ $video->title }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Info --}}
                <div class="lg:py-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-champagne-deep font-medium mb-2">{{ $product->metal ?? 'Jewellery' }}</div>
                    <h1 class="font-serif text-3xl md:text-4xl lg:text-5xl leading-tight mb-3">{{ $product->name }}</h1>

                    @if ($product->rating_count > 0)
                        <div class="flex items-center gap-2 text-sm mb-5">
                            <span class="text-champagne-deep">★★★★★</span>
                            <span class="font-medium">{{ number_format($product->rating_average, 1) }}</span>
                            <span class="text-muted">({{ $product->rating_count }} verified reviews)</span>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-baseline gap-3 mb-6">
                        @php
                            $price = $product->currentPrice();
                            $discount = $product->discountPercentage();
                        @endphp
                        <span class="font-serif text-3xl md:text-4xl text-charcoal">Rs. {{ number_format($price) }}</span>
                        @if ($discount)
                            <span class="text-base text-muted line-through self-center">Rs. {{ number_format($product->regular_price) }}</span>
                            <span class="badge bg-rose text-charcoal">Save {{ $discount }}%</span>
                        @endif
>>>>>>> origin/main
                    </div>

                    @if ($product->short_description)
                        <p class="mt-4 text-sm leading-7 text-charcoal-soft sm:text-base">
                            {{ $product->short_description }}
                        </p>
                    @endif

<<<<<<< HEAD
                    {{-- Benefits --}}
                    <div class="my-5 grid grid-cols-2 gap-3 border-y border-champagne-dark/10 py-5 sm:grid-cols-4">
                        @foreach ([['◇','Premium','Quality'],['♧','Skin','Friendly'],['♙','Secure','Packaging'],['⟳','Easy','Returns']] as [$icon,$line1,$line2])
                            <div class="flex flex-col items-center rounded-lg bg-porcelain p-3 text-center">
                                <span class="mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-champagne-pale text-xl text-champagne-deep">
                                    {{ $icon }}
                                </span>

                                <span class="text-xs font-medium">{{ $line1 }}</span>
                                <span class="text-xs text-muted">{{ $line2 }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Variant selection and cart --}}
                    @if ($variantPicker)
                        <form action="{{ route('cart.items.store') }}" method="post">
                            @csrf

                            <input
                                type="hidden"
                                name="product_variant_id"
                                value="{{ $selectedVariant?->id }}"
                                :value="selectedVariantId"
                            >

                            <fieldset class="mb-5" aria-label="Product options">
                                @if ($optionGroups->isEmpty())
                                    {{-- Title-only variants: fall back to a plain select. --}}
                                    <div class="mb-4">
                                        <label for="variant-select" class="mb-2 block text-sm font-medium">
                                            Choose an option
                                        </label>

                                        <select
                                            id="variant-select"
                                            x-model.number="selectedVariantId"
                                            @change="$nextTick(() => selectVariant(selectedVariantId))"
                                            class="w-full max-w-xs rounded-lg border border-champagne-dark/20 bg-porcelain px-3 py-2.5 text-sm"
                                        >
                                            @foreach ($sellableVariants as $variant)
                                                <option value="{{ $variant->id }}" @selected($selectedVariant?->id === $variant->id)>
                                                    {{ $variant->displayLabel() }} — Rs. {{ number_format($variant->currentPrice()) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    @foreach ($optionGroups as $groupName => $groupValues)
                                        <div class="{{ $loop->last ? '' : 'mb-4' }}">
                                            <div class="mb-2 flex items-baseline gap-2 text-sm">
                                                <span class="font-medium">{{ $groupName }}</span>

                                                <span
                                                    class="text-muted"
                                                    x-show="selected[@js($groupName)]"
                                                    x-cloak
                                                >
                                                    <span class="sr-only">Selected:</span>
                                                    <span x-text="selected[@js($groupName)]"></span>
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap gap-2" role="group" aria-label="{{ $groupName }}">
                                                @foreach ($groupValues as $value)
                                                    <button
                                                        type="button"
                                                        @click="pickOption(@js($groupName), @js($value))"
                                                        :aria-pressed="selected[@js($groupName)] === @js($value)"
                                                        :disabled="!optionAvailable(@js($groupName), @js($value)) && selected[@js($groupName)] !== @js($value)"
                                                        :class="optionClass(@js($groupName), @js($value))"
                                                        class="rounded-lg border px-4 py-2.5 text-sm transition"
                                                    >
                                                        {{ $value }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </fieldset>

                            <div class="mb-4 flex flex-wrap items-center gap-3">
                                <label for="variant-quantity" class="text-sm font-medium">
                                    Quantity
                                </label>

                                <div class="inline-flex items-center rounded-lg border border-champagne-dark/20">
                                    <button
                                        type="button"
                                        @click="decreaseQuantity()"
                                        class="px-3 py-2.5"
                                        aria-label="Decrease quantity"
                                    >−</button>

                                    <input
                                        id="variant-quantity"
                                        type="number"
                                        name="quantity"
                                        x-model.number="quantity"
                                        @change="clampQuantity()"
                                        min="1"
                                        :max="quantityMax"
                                        required
                                        class="w-12 border-0 bg-transparent p-0 text-center focus:ring-0"
                                    >

                                    <button
                                        type="button"
                                        @click="increaseQuantity()"
                                        class="px-3 py-2.5"
                                        aria-label="Increase quantity"
                                    >+</button>
                                </div>

                                <span
                                    class="text-xs text-warning"
                                    x-show="currentVariant?.stock > 0 && currentVariant?.stock <= currentVariant?.lowThreshold"
                                    x-text="'Only ' + currentVariant.stock + ' left'"
                                    x-cloak
                                ></span>
                            </div>

                            <p
                                class="mb-4 text-xs text-muted"
                                x-show="currentVariant && currentVariant.stock <= 0 && currentVariant.backorder"
                                x-cloak
                            >
                                Available on backorder — we'll ship it as soon as it is restocked.
                            </p>

                            <button
                                type="submit"
                                class="btn-gold min-h-12 w-full rounded-lg py-3 text-sm font-medium"
                                :disabled="!canAddToCart"
                                :class="{ 'cursor-not-allowed opacity-50': !canAddToCart }"
                                x-text="canAddToCart ? '♧  Add to Cart' : 'Out of Stock'"
                            >
                                ♧ &nbsp; Add to Cart
                            </button>
                        </form>

                    @elseif ($selectedVariant && ($selectedVariant->inStock() || $product->allow_backorders))
                        <form action="{{ route('cart.items.store') }}" method="post" class="mt-2">
                            @csrf

                            <input
                                type="hidden"
                                name="product_variant_id"
                                value="{{ $selectedVariant->id }}"
                            >

                            <div class="mb-4 flex items-center gap-3">
                                <label for="simple-quantity" class="text-sm font-medium">
                                    Quantity
                                </label>

                                <div class="inline-flex items-center rounded-lg border border-champagne-dark/20">
                                    <button
                                        type="button"
                                        @click="quantity = Math.max(1, quantity - 1)"
                                        class="px-3 py-2.5"
                                        aria-label="Decrease quantity"
                                    >−</button>

                                    <input
                                        id="simple-quantity"
                                        type="number"
                                        name="quantity"
                                        x-model.number="quantity"
                                        min="1"
                                        max="20"
                                        required
                                        class="w-12 border-0 bg-transparent p-0 text-center focus:ring-0"
                                    >

                                    <button
                                        type="button"
                                        @click="quantity = Math.min(20, quantity + 1)"
                                        class="px-3 py-2.5"
                                        aria-label="Increase quantity"
                                    >+</button>
                                </div>
                            </div>

                            <button type="submit" class="btn-gold min-h-12 w-full rounded-lg py-3 text-sm font-medium">
                                ♧ &nbsp; Add to Cart
                            </button>
                        </form>
                    @endif

                    {{-- Wishlist --}}
                    @if (config('app.features.wishlist'))
                        <form action="{{ route('wishlist.add.public') }}" method="post" class="mt-3">
                            @csrf

                            <input type="hidden" name="product_id" value="{{ $product->id }}">

                            <button
                                type="submit"
                                class="btn-outline min-h-11 w-full rounded-lg border border-champagne-dark/30 py-2.5 text-sm transition hover:bg-champagne-pale"
                            >
                                ♡ &nbsp; Add to Wishlist
                            </button>
                        </form>
                    @endif

                    {{-- Delivery information --}}
                    <div class="mt-5 grid grid-cols-1 gap-3 border-t border-champagne-dark/10 pt-4 text-xs text-charcoal-soft sm:grid-cols-3">
                        <div class="flex items-start gap-2">
                            <span class="text-lg">♧</span>
                            <div>
                                <p class="font-medium">Free Shipping</p>
                                <p class="mt-1 text-muted">On orders above Rs. 5,000</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="text-lg">▤</span>
                            <div>
                                <p class="font-medium">Secure Payment</p>
                                <p class="mt-1 text-muted">Secure checkout</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-2">
                            <span class="text-lg">⟳</span>
                            <div>
                                <p class="font-medium">Easy Returns</p>
                                <p class="mt-1 text-muted">7-day return policy</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Brand benefits --}}
            <section class="my-8 grid grid-cols-1 gap-px overflow-hidden rounded-xl border border-champagne-dark/10 bg-champagne-dark/10 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([['◇','Certified Diamonds','With authenticity certificate'],['♧','Luxury Packaging','Perfect for gifting'],['◎','Worldwide Shipping','Delivering elegance globally'],['♡','Lifetime Care','Professional cleaning and support']] as [$icon,$title,$copy])
                    <div class="flex items-center gap-3 bg-champagne-pale px-4 py-4 sm:px-5">
                        <span class="text-2xl text-champagne-deep">{{ $icon }}</span>

                        <div class="min-w-0">
                            <p class="font-serif text-sm">{{ $title }}</p>
                            <p class="mt-1 text-xs text-muted">{{ $copy }}</p>
                        </div>
                    </div>
                @endforeach
            </section>

            {{-- Product information --}}
            <section class="mb-10">
                <div
                    class="flex gap-5 overflow-x-auto border-b border-champagne-dark/15 text-sm"
                    role="tablist"
                    aria-label="Product information"
                >
                    @foreach ([
                        'description' => 'Description',
                        'specifications' => 'Specifications',
                        'shipping' => 'Shipping & Returns',
                        'reviews' => 'Reviews (' . $product->rating_count . ')',
                        'care' => 'Care Guide',
                    ] as $key => $label)
                        <button
                            type="button"
                            id="{{ $key }}-tab"
                            role="tab"
                            aria-controls="{{ $key }}-panel"
                            @click="tab = '{{ $key }}'"
                            :aria-selected="tab === '{{ $key }}'"
                            :class="tab === '{{ $key }}'
                                ? 'border-champagne-dark text-champagne-dark'
                                : 'border-transparent text-charcoal-soft'"
                            class="shrink-0 whitespace-nowrap border-b-2 px-1 py-4 transition"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="grid gap-6 rounded-b-xl border border-t-0 border-champagne-dark/10 p-4 sm:p-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(240px,.8fr)] lg:p-8">

                    <div
                        x-show="tab === 'description'"
                        x-transition.opacity.duration.200ms
                        id="description-panel"
                        role="tabpanel"
                        aria-labelledby="description-tab"
                        x-cloak
                    >
                        <h2 class="font-serif text-2xl">Product Description</h2>

                        <div class="prose prose-sm mt-4 max-w-none text-charcoal-soft">
                            {!! $product->description ?: '<p>' . e($product->short_description ?? $product->name) . '</p>' !!}
                        </div>

                        <ul class="mt-5 space-y-2 text-sm text-charcoal-soft">
                            @foreach ([
                                'Crafted with carefully selected materials',
                                'Elegant design with intricate detailing',
                                'Suitable for weddings and special occasions',
                                'A timeless addition to your collection',
                            ] as $point)
                                <li>✓ &nbsp;{{ $point }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div
                        x-show="tab === 'specifications'"
                        x-transition.opacity.duration.200ms
                        id="specifications-panel"
                        role="tabpanel"
                        aria-labelledby="specifications-tab"
                        x-cloak
                    >
                        <h2 class="font-serif text-2xl">Specifications</h2>

                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-4 text-sm">
                            @foreach ([
                                'Metal' => $product->metal,
                                'Gemstone' => $product->gemstone,
                                'Carat' => $product->carat,
                                'Weight' => $product->weight_grams ? $product->weight_grams . ' g' : null,
                                'Dimensions' => $product->dimensions,
                                'SKU' => $product->sku,
                            ] as $label => $value)
                                @if (filled($value))
                                    <dt class="text-muted">{{ $label }}</dt>
                                    <dd class="break-words font-medium">{{ $value }}</dd>
                                @endif
                            @endforeach
                        </dl>
                    </div>

                    <div
                        x-show="tab === 'shipping'"
                        x-transition.opacity.duration.200ms
                        id="shipping-panel"
                        role="tabpanel"
                        aria-labelledby="shipping-tab"
                        x-cloak
                    >
                        <h2 class="font-serif text-2xl">Shipping & Returns</h2>

                        <p class="mt-4 text-sm leading-7 text-charcoal-soft">
                            Complimentary shipping on eligible orders above Rs. 5,000.
                            If your piece isn't quite right, contact our team within
                            7 days of delivery to arrange a return in accordance with
                            our returns policy.
                        </p>
                    </div>

                    <div
                        x-show="tab === 'care'"
                        x-transition.opacity.duration.200ms
                        id="care-panel"
                        role="tabpanel"
                        aria-labelledby="care-tab"
                        x-cloak
                    >
                        <h2 class="font-serif text-2xl">Care Guide</h2>

                        <div class="prose prose-sm mt-4 max-w-none text-charcoal-soft">
                            {!! $product->care_instructions ?: '<p>Store your jewellery separately in its pouch. Keep it away from perfumes, moisture and harsh chemicals. Wipe gently with a soft cloth after wearing.</p>' !!}
                        </div>
                    </div>

                    <div
                        x-show="tab === 'reviews'"
                        x-transition.opacity.duration.200ms
                        id="reviews"
                        role="tabpanel"
                        aria-labelledby="reviews-tab"
                        x-cloak
                    >
                        <h2 class="font-serif text-2xl">
                            Customer Reviews
                            <span class="text-base">({{ $product->rating_count }})</span>
                        </h2>

                        @if ($product->rating_count > 0)
                            <p class="mt-3 text-lg">
                                {{ number_format($product->rating_average, 1) }}
                                <span class="text-warning">★★★★★</span>
                            </p>
                        @endif

                        @if ($product->allow_reviews && $product->approvedReviews->isNotEmpty())
                            <div class="mt-5 space-y-4">
                                @foreach ($product->approvedReviews->take(5) as $review)
                                    <article class="border-t border-champagne-dark/10 pt-4">
                                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                            <span class="font-medium">
                                                {{ $review->user->first_name }}
                                                {{ filled($review->user->last_name ?? null) ? substr($review->user->last_name, 0, 1) . '.' : '' }}
                                                <span class="text-warning">★★★★★</span>
                                            </span>

                                            <time class="text-xs text-muted">
                                                {{ $review->created_at->format('j M Y') }}
                                            </time>
                                        </div>

                                        <p class="mt-2 text-sm leading-6 text-charcoal-soft">
                                            {{ $review->comment }}
                                        </p>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-4 text-sm text-muted">
                                Be the first to share your experience with this piece.
                            </p>
                        @endif
                    </div>

                    {{-- Supporting image --}}
                    <div class="hidden lg:block">
                        <div class="aspect-[4/5] overflow-hidden rounded-xl bg-ivory-alt">
                            <img
                                src="{{ $imageUrls->first() }}"
                                alt="{{ $product->name }}"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            >
                        </div>
                    </div>
                </div>
            </section>

            {{-- Related products --}}
            @if ($relatedProducts->isNotEmpty())
                <section class="mb-10">
                    <div class="mb-5 flex items-end justify-between gap-4 border-b border-champagne-light pb-3">
                        <div>
                            <p class="mb-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-champagne-deep">
                                Complete your look
                            </p>

                            <h2 class="font-serif text-2xl sm:text-3xl">
                                You May Also Like
                            </h2>
                        </div>

                        <a
                            href="{{ route('shop.index') }}"
                            class="shrink-0 text-xs text-champagne-dark hover:underline sm:text-sm"
                        >
                            View All →
                        </a>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                        @foreach ($relatedProducts->take(5) as $related)
                            @include('storefront.product.card', ['product' => $related])
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Recently viewed --}}
            @if ($recentlyViewedProducts->isNotEmpty())
                <section class="mb-10">
                    <h2 class="mb-5 font-serif text-2xl sm:text-3xl">
                        Recently Viewed
                    </h2>

                    <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($recentlyViewedProducts->take(4) as $recent)
                            @include('storefront.product.card', ['product' => $recent])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </main>

            {{-- Mobile sticky purchase bar (inside <main> so it shares the Alpine scope) --}}
            @if ($selectedVariant)
                <div
                    class="fixed inset-x-0 bottom-0 z-40 border-t border-champagne-dark/15 bg-ivory/95 p-3 shadow-lg backdrop-blur-md lg:hidden"
                    style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));"
                >
                    <div class="container-haars mx-auto flex max-w-7xl items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs text-muted" x-show="currentVariant" x-text="currentVariant?.label || @js($product->name)" x-cloak>
                                {{ $product->name }}
                            </p>

                            <p
                                class="font-serif text-lg text-charcoal"
                                x-text="formattedPrice"
                            >
                                Rs. {{ number_format($selectedVariant->currentPrice()) }}
                            </p>
                        </div>

                        <form action="{{ route('cart.items.store') }}" method="post" class="shrink-0">
                            @csrf

                            <input
                                type="hidden"
                                name="product_variant_id"
                                value="{{ $selectedVariant->id }}"
                                :value="selectedVariantId"
                            >

                            <input
                                type="hidden"
                                name="quantity"
                                value="1"
                                :value="quantity"
                            >

                            <button
                                type="submit"
                                class="btn-gold min-h-11 rounded-lg px-5 py-2.5 text-sm font-medium"
                                :disabled="!canAddToCart"
                                :class="{ 'cursor-not-allowed opacity-50': !canAddToCart }"
                                x-text="canAddToCart ? 'Add to Cart' : 'Out of Stock'"
                            >
                                Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('variantPicker', (config) => ({
                variants: config.variants ?? {},
                groups: config.groups ?? [],
                selected: { ...(config.initialSelection ?? {}) },
                selectedVariantId: config.initialVariantId ?? null,
                quantity: 1,
                maxQuantity: config.maxQuantity ?? 20,
                activeImage: 0,
                imageUrls: config.imageUrls ?? [],
                tab: 'description',
                fallbackPrice: config.fallbackPrice ?? 0,

                init() {
                    // Land on the photo of the pre-selected variant, if it has one.
                    const initial = this.currentVariant;
                    if (initial && initial.image != null) {
                        this.activeImage = initial.image;
                    }
                },

                get currentVariant() {
                    return this.variants[this.selectedVariantId] ?? null;
                },

                get canAddToCart() {
                    const variant = this.currentVariant;
                    if (!variant) return false;
                    return variant.stock > 0 || variant.backorder;
                },

                get quantityMax() {
                    const variant = this.currentVariant;
                    if (!variant || variant.stock <= 0) return this.maxQuantity;
                    return Math.max(1, Math.min(this.maxQuantity, variant.stock));
                },

                get formattedPrice() {
                    const variant = this.currentVariant;
                    const price = variant ? variant.price : (this.fallbackPrice ?? 0);
                    return 'Rs. ' + Number(price).toLocaleString('en-PK');
                },

                get stockLabel() {
                    const variant = this.currentVariant;
                    if (!variant) return '';

                    if (variant.stock > 0) {
                        return variant.stock <= variant.lowThreshold
                            ? 'Low stock — only ' + variant.stock + ' left'
                            : 'In stock and ready to ship';
                    }

                    return variant.backorder ? 'Available on backorder' : 'Currently unavailable';
                },

                get stockBadgeClass() {
                    const variant = this.currentVariant;
                    if (variant && (variant.stock > 0 || variant.backorder)) {
                        return 'bg-success-light text-success';
                    }
                    return 'bg-danger-light text-danger';
                },

                formatMoney(value) {
                    return 'Rs. ' + Number(value ?? 0).toLocaleString('en-PK');
                },

                changeImage(direction) {
                    this.activeImage =
                        (this.activeImage + direction + this.imageUrls.length)
                        % this.imageUrls.length;
                },

                decreaseQuantity() {
                    this.quantity = Math.max(1, this.quantity - 1);
                },

                increaseQuantity() {
                    this.quantity = Math.min(this.quantityMax, this.quantity + 1);
                },

                clampQuantity() {
                    const parsed = parseInt(this.quantity, 10);
                    this.quantity = Number.isNaN(parsed)
                        ? 1
                        : Math.min(Math.max(1, parsed), this.quantityMax);
                },

                selectVariant(id) {
                    this.selectedVariantId = id;
                    const variant = this.variants[id];
                    if (!variant) return;

                    if (variant.image != null) {
                        this.activeImage = variant.image;
                    }

                    this.quantity = Math.min(this.quantity, this.quantityMax);
                },

                pickOption(name, value) {
                    if (!this.optionAvailable(name, value)) return;

                    this.selected[name] = value;

                    let id = this.matchingVariantId();

                    if (!id) {
                        // No exact combination: adopt the closest variant so the
                        // selection never points at a stale variant.
                        const closest = Object.values(this.variants).find((variant) =>
                            Object.entries(this.selected).every(([key, val]) =>
                                key === name || !val || variant.attributes[key] === val
                            )
                        );

                        if (closest) {
                            Object.keys(this.selected).forEach((key) => {
                                if (key !== name && this.selected[key]) {
                                    this.selected[key] = closest.attributes[key];
                                }
                            });
                            id = closest.id;
                        }
                    }

                    if (id) this.selectVariant(id);
                },

                matchingVariantId() {
                    const decided = Object.entries(this.selected).filter(([, val]) => val);
                    if (decided.length === 0) return null;

                    const match = Object.values(this.variants).find((variant) =>
                        decided.every(([name, val]) => variant.attributes[name] === val)
                    );

                    return match ? match.id : null;
                },

                optionAvailable(name, value) {
                    return Object.values(this.variants).some((variant) =>
                        variant.attributes[name] === value &&
                        Object.entries(this.selected).every(([key, val]) =>
                            key === name || !val || variant.attributes[key] === val
                        ) &&
                        (variant.stock > 0 || variant.backorder)
                    );
                },

                optionClass(name, value) {
                    const selected = this.selected[name] === value;
                    const available = this.optionAvailable(name, value);

                    let classes = selected
                        ? 'border-champagne-dark bg-champagne-pale'
                        : 'border-champagne-dark/20 hover:border-champagne-dark';

                    if (!available && !selected) {
                        classes += ' cursor-not-allowed line-through opacity-40 hover:border-champagne-dark/20';
                    }

                    return classes;
                },
            }));
        });
    </script>
@endpush
=======
                    {{-- Variants --}}
                    @if ($product->has_variants && $product->variants->isNotEmpty())
                        <form action="{{ route('cart.items.store') }}" method="post" x-data="{
                            selectedVariant: null,
                            init() {
                                this.selectedVariant = {{ $product->variants->where('is_default', true)->first()?->id ?? $product->variants->first()?->id }};
                            }
                        }">
                            @csrf
                            <div class="space-y-4 mb-6">
                                @foreach ($product->variants as $variant)
                                    @if ($variant->inStock())
                                    <label class="block p-4 border border-charcoal/10 rounded-lg cursor-pointer hover:border-champagne transition-colors">
                                        <input type="radio" name="product_variant_id" value="{{ $variant->id }}" class="hidden peer" @checked($variant->is_default)>
                                        <div class="peer-checked:border-champagne peer-checked:bg-ivory-alt/30 -m-4 p-4 rounded-lg flex items-center justify-between">
                                            <div>
                                                <div class="font-medium">{{ $variant->title ?? $variant->formattedAttributes() }}</div>
                                                @if ($variant->sale_price)
                                                    <div class="text-sm text-rose text-rose-deep">Sale: Rs. {{ number_format($variant->sale_price) }} <span class="line-through text-muted">Rs. {{ number_format($variant->regular_price) }}</span></div>
                                                @else
                                                    <div class="text-sm text-charcoal-soft">Rs. {{ number_format($variant->regular_price) }}</div>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <span class="text-sm font-medium text-champagne-deep">Rs. {{ number_format($variant->currentPrice()) }}</span>
                                            </div>
                                        </div>
                                    </label>
                                    @endif
                                @endforeach
                            </div>

                            {{-- Quantity --}}
                            <div class="mb-6">
                                <label for="quantity" class="text-sm font-medium text-charcoal-soft mb-2 block">Quantity</label>
                                <div class="inline-flex items-center border border-charcoal/10 rounded-lg overflow-hidden">
                                    <button type="button" onclick="this.nextElementSibling.stepDown(); this.form.submit()" class="px-4 py-2.5 text-charcoal-soft hover:text-charcoal hover:bg-ivory-alt/50 transition-colors" aria-label="Decrease quantity">−</button>
                                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="20" class="w-16 text-center border-0 focus:ring-0 text-base">
                                    <button type="button" onclick="this.previousElementSibling.stepUp(); this.form.submit()" class="px-4 py-2.5 text-charcoal-soft hover:text-charcoal hover:bg-ivory-alt/50 transition-colors" aria-label="Increase quantity">+</button>
                                </div>
                            </div>

                            {{-- Add to cart --}}
                            @if ($product->inStock())
                                <button type="submit" class="btn-gold w-full text-base py-3 text-lg" id="add-to-cart-btn">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><path d="M3 5l3 .5 1.5 11a2 2 0 0 0 2 1.7h9a2 2 0 0 0 2-1.7L22 8H6"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg>
                                    Add to Cart
                                </button>
                            @else
                                <button type="button" class="btn-secondary w-full text-base py-3 text-lg" disabled>
                                    Out of Stock
                                </button>
                            @endif
                        </form>
                    @endif

                    {{-- Details & specs --}}
                    <div class="mt-10 pt-8 border-t border-charcoal/5">
                        <h2 class="font-serif text-xl mb-6">Details</h2>
                        <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                            @if ($product->metal)
                                <dt class="text-muted">Metal</dt>
                                <dd class="font-medium">{{ $product->metal }}</dd>
                            @endif
                            @if ($product->gemstone)
                                <dt class="text-muted">Gemstone</dt>
                                <dd class="font-medium">{{ $product->gemstone }}</dd>
                            @endif
                            @if ($product->carat)
                                <dt class="text-muted">Carat</dt>
                                <dd class="font-medium">{{ $product->carat }}</dd>
                            @endif
                            @if ($product->weight_grams)
                                <dt class="text-muted">Weight</dt>
                                <dd class="font-medium">{{ $product->weight_grams }} grams</dd>
                            @endif
                            @if ($product->dimensions)
                                <dt class="text-muted">Dimensions</dt>
                                <dd class="font-medium">{{ $product->dimensions }}</dd>
                            @endif
                            @if ($product->sku)
                                <dt class="text-muted">SKU</dt>
                                <dd class="font-mono font-medium">{{ $product->sku }}</dd>
                            @endif
                        </dl>
                    </div>

                    {{-- Description --}}
                    @if ($product->description)
                        <div class="mt-10 pt-8 border-t border-charcoal/5">
                            <h2 class="font-serif text-xl mb-4">Description</h2>
                            <div class="prose prose-sm max-w-none text-charcoal-soft leading-relaxed">
                                {!! $product->description !!}
                            </div>
                        </div>
                    @endif

                    {{-- Care instructions --}}
                    @if ($product->care_instructions)
                        <div class="mt-10 pt-8 border-t border-charcoal/5">
                            <h2 class="font-serif text-xl mb-4">Care Instructions</h2>
                            <div class="prose prose-sm max-w-none text-charcoal-soft leading-relaxed">
                                {!! $product->care_instructions !!}
                            </div>
                        </div>
                    @endif

                    {{-- Reviews --}}
                    @if ($product->allow_reviews && $product->approvedReviews->isNotEmpty())
                        <div class="mt-10 pt-8 border-t border-charcoal/5">
                            <h2 class="font-serif text-xl mb-6">Reviews ({{ $product->rating_count }})</h2>
                            <div class="space-y-6">
                                @foreach ($product->approvedReviews->take(5) as $review)
                                    <div class="p-5 bg-ivory-alt/30 rounded-lg">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="text-champagne-deep">★★★★★</span>
                                                <span class="font-medium">{{ $review->user->first_name }} {{ $review->user->last_name[0] }}.</span>
                                            </div>
                                            <time class="text-xs text-muted">{{ $review->created_at->format('j F Y') }}</time>
                                        </div>
                                        <p class="text-sm text-charcoal-soft leading-relaxed">{{ $review->comment }}</p>
                                    </div>
                                @endforeach
                            </div>
                            @if ($product->approvedReviews->count() > 5)
                                <a href="{{ route('products.show', $product) }}#reviews" class="btn-outline mt-6">View all reviews</a>
                            @endif
                        </div>
                    @endif

                    {{-- Recently viewed --}}
                    @if ($recentlyViewedProducts->isNotEmpty())
                        <section class="mt-16 pt-8 border-t border-charcoal/5">
                            <h2 class="font-serif text-xl mb-6">Recently Viewed</h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                                @foreach ($recentlyViewedProducts as $recent)
                                    @include('storefront.product.card', ['product' => $recent])
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Related products --}}
                    @if ($relatedProducts->isNotEmpty())
                        <section class="mt-16 pt-8 border-t border-charcoal/5">
                            <h2 class="font-serif text-xl mb-6">You may also love</h2>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                                @foreach ($relatedProducts as $related)
                                    @include('storefront.product.card', ['product' => $related])
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Sticky add-to-cart bar (mobile) --}}
    @if ($product->inStock())
        <div class="fixed bottom-0 left-0 right-0 bg-porcelain border-t border-charcoal/10 p-4 lg:hidden z-40" x-data x-show="true" x-transition>
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <p class="font-serif text-sm truncate">{{ $product->name }}</p>
                    <p class="font-serif text-lg text-charcoal">Rs. {{ number_format($product->currentPrice()) }}</p>
                </div>
                <form action="{{ route('cart.items.store') }}" method="post" class="flex-shrink-0">
                    @csrf
                    @php $defaultVariant = $product->variants->where('is_default', true)->first() ?? $product->variants->first(); @endphp
                    @if ($defaultVariant)
                        <input type="hidden" name="product_variant_id" value="{{ $defaultVariant->id }}">
                    @endif
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn-gold px-6 py-2.5 text-sm">Add to Cart</button>
                </form>
            </div>
        </div>
    @endif
@endsection
>>>>>>> origin/main
