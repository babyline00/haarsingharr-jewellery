@extends('layouts.storefront')

@section('title', $product->seo_title ?? $product->name . ' — Haarsingharr')

@section('meta_description', $product->seo_description ?? \Illuminate\Support\Str::limit($product->short_description ?? $product->description, 160))

@section('structured_data')
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
        }
    }
    </script>
@endsection

@section('content')
    <section class="py-10 md:py-14">
        <div class="container-haars">
            {{-- Breadcrumbs --}}
            <nav class="text-xs text-muted mb-6" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-champagne transition-colors">Home</a>
                <span class="mx-1.5">/</span>
                <a href="{{ route('shop.index') }}" class="hover:text-champagne transition-colors">Shop</a>
                @if ($product->category)
                    <span class="mx-1.5">/</span>
                    <a href="{{ route('categories.show', $product->category) }}" class="hover:text-champagne transition-colors">{{ $product->category->name }}</a>
                @endif
                <span class="mx-1.5">/</span>
                <span class="text-charcoal-soft">{{ $product->name }}</span>
            </nav>

            <div class="grid lg:grid-cols-2 gap-8 lg:gap-14">

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
                    </div>

                    @if ($product->short_description)
                        <p class="text-sm text-charcoal-soft leading-relaxed mb-6">{{ $product->short_description }}</p>
                    @endif

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