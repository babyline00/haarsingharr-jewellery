@extends('layouts.storefront')

@section('title', $product->seo_title ?? $product->name . ' — Haarsingharr')

@section('meta_description', $product->seo_description ?? \Illuminate\Support\Str::limit($product->short_description ?? $product->description, 160))

@section('structured_data')
    <script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": {{ json_encode($product->name) }},
        "image": [{{ json_encode(asset('storage/'.$product->primaryImagePath())) }}],
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
            <nav class="text-xs text-muted mb-6">
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
                <div x-data="{ active: 0, open: false }">
                    @php
                        $images = $product->images;
                    @endphp
                    <div class="grid grid-cols-1 gap-3">
                        <div class="aspect-square relative bg-ivory-alt rounded-2xl overflow-hidden">
                            @if ($images->isNotEmpty())
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($images->first()->path) }}"
                                     alt="{{ $images->first()->alt_text ?? $product->name }}"
                                     class="w-full h-full object-cover cursor-zoom-in"
                                     @click="open = true">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-muted/40">Haarsingharr</div>
                            @endif
                        </div>
                        @if ($images->count() > 1)
                            <div class="grid grid-cols-5 gap-2">
                                @foreach ($images as $index => $image)
                                    <button @click="active = {{ $loop->index }}" class="aspect-square rounded-md overflow-hidden border-2 {{ $loop->first ? 'border-champagne' : 'border-transparent' }}" :class="active === {{ $loop->index }} ? 'border-champagne' : 'border-transparent'">
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" alt="{{ $image->alt_text ?? '' }}" class="w-full h-full object-cover">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Info --}}
                <div class="lg:py-4">
                    <div class="text-xs uppercase tracking-[0.25em] text-champagne-dark font-medium mb-2">{{ $product->metal ?? 'Jewellery' }}</div>
                    <h1 class="font-serif text-3xl md:text-4xl leading-tight mb-3">{{ $product->name }}</h1>

                    @if ($product->rating_count > 0)
                        <div class="flex items-center gap-2 text-sm mb-5">
                            <span class="text-champagne">★★★★★</span>
                            <span class="font-medium">{{ number_format($product->rating_average, 1) }}</span>
                            <span class="text-muted">({{ $product->rating_count }} verified reviews)</span>
                        </div>
                    @endif

                    <div class="flex items-baseline gap-3 mb-6">
                        @php
                            $price = $product->currentPrice();
                            $discount = $product->discountPercentage();
                        @endphp
                        <span class="font-serif text-3xl text-charcoal">Rs. {{ number_format($price) }}</span>
                        @if ($discount)
                            <span class="text-base text-muted line-through">Rs. {{ number_format($product->regular_price) }}</span>
                            <span class="badge bg-rose text-ivory">Save {{ $discount }}%</span>
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
                            {{-- Variant options — for now grouped by attribute key --}}
                            @foreach (collect($product->variant_attributes ?? [])->unique() as $attr)
                                <div class="mb-5">
                                    <label class="text-xs font-medium uppercase tracking-widest text-charcoal-soft">Select {{ ucfirst($attr) }}</label>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        @foreach ($product->variants as $variant)
                                            @php
                                                $value = $variant->attributes[$attr] ?? null;
                                            @endphp
                                            @if ($value)
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="product_variant_id" value="{{ $variant->id }}" x-model="selectedVariant" class="peer hidden" @if ($variant->is_default) checked @endif>
                                                    <span class="block px-3 py-1.5 text-xs border border-charcoal/10 rounded-full peer-checked:bg-charcoal peer-checked:text-ivory peer-checked:border-charcoal transition-colors hover:border-champagne">
                                                        {{ $value }}
                                                    </span>
                                                </label>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach

                            {{-- Quantity & add to cart --}}
                            <div class="flex items-stretch gap-3 mb-4">
                                <div class="inline-flex items-center border border-charcoal/10 rounded-md">
                                    <button type="button" onclick="this.nextElementSibling.stepDown()" class="px-3 py-2 text-charcoal-soft hover:text-charcoal transition-colors" aria-label="Decrease">−</button>
                                    <input type="number" name="quantity" value="1" min="1" max="20" class="w-14 text-center border-0 focus:ring-0" style="appearance: none;">
                                    <button type="button" onclick="this.previousElementSibling.stepUp()" class="px-3 py-2 text-charcoal-soft hover:text-charcoal transition-colors" aria-label="Increase">+</button>
                                </div>
                                <button type="submit" class="btn-gold flex-1" {{ $product->inStock() ? '' : 'disabled' }}>
                                    {{ $product->inStock() ? 'Add to Cart' : 'Out of Stock' }}
                                </button>
                            </div>
                        </form>
                    @elseif ($product->inStock())
                        <form action="{{ route('cart.items.store') }}" method="post">
                            @csrf
                            @php $defaultVariant = $product->variants->where('is_default', true)->first() ?? $product->variants->first(); @endphp
                            @if ($defaultVariant)
                                <input type="hidden" name="product_variant_id" value="{{ $defaultVariant->id }}">
                            @endif
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn-gold w-full mb-3">Add to Cart</button>
                        </form>
                    @else
                        <button disabled class="btn-outline w-full mb-3 cursor-not-allowed">Currently Out of Stock</button>
                    @endif

                    {{-- Trust --}}
                    <div class="grid grid-cols-2 gap-2 text-xs text-muted mt-6 pt-6 border-t border-charcoal/5">
                        <div class="flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><line x1="16" y1="8" x2="20" y2="8"/></svg>
                            Free nationwide delivery
                        </div>
                        <div class="flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.7 9.7 0 0 0-6 2L3 3v6"/></svg>
                            7-day exchange
                        </div>
                        <div class="flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L3 6v6c0 5 4 9 9 10 5-1 9-5 9-10V6l-9-4z"/></svg>
                            Hallmarked 22k gold
                        </div>
                        <div class="flex items-center gap-2">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.4 8.4 0 0 1-12 7.6L4 22l1.9-5A8.4 8.4 0 1 1 21 11.5z"/></svg>
                            Private consultations
                        </div>
                    </div>
                </div>
            </div>

            {{-- Description & details --}}
            <div class="mt-16 grid md:grid-cols-3 gap-12">
                <div class="md:col-span-2">
                    <h2 class="font-serif text-2xl mb-4">Details</h2>
                    @if ($product->description)
                        <div class="prose prose-sm max-w-none text-charcoal-soft leading-relaxed">
                            {!! $product->description !!}
                        </div>
                    @endif

                    <h3 class="font-serif text-xl mt-8 mb-3">Specifications</h3>
                    <dl class="grid grid-cols-2 gap-y-3 text-sm">
                        @if ($product->metal)        <dt class="text-muted">Metal</dt><dd>{{ $product->metal }}</dd> @endif
                        @if ($product->gemstone)     <dt class="text-muted">Gemstone</dt><dd>{{ $product->gemstone }}</dd> @endif
                        @if ($product->carat)        <dt class="text-muted">Carat</dt><dd>{{ $product->carat }}ct</dd> @endif
                        @if ($product->weight_grams) <dt class="text-muted">Weight</dt><dd>{{ $product->weight_grams }}g</dd> @endif
                        @if ($product->dimensions)   <dt class="text-muted">Dimensions</dt><dd>{{ $product->dimensions }}</dd> @endif
                        @if ($product->material)     <dt class="text-muted">Material</dt><dd>{{ $product->material }}</dd> @endif
                    </dl>

                    @if ($product->care_instructions)
                        <h3 class="font-serif text-xl mt-8 mb-3">Care Instructions</h3>
                        <p class="text-sm text-charcoal-soft leading-relaxed">{{ $product->care_instructions }}</p>
                    @endif
                </div>

                <aside>
                    <h3 class="font-serif text-xl mb-3">Need Help?</h3>
                    <p class="text-sm text-muted leading-relaxed mb-4">Book a private consultation with our jewellers, or chat with us on WhatsApp.</p>
                    <a href="{{ route('contact') }}" class="btn-outline text-sm w-full mb-2">Book a Consultation</a>
                    @if (config('storefront.notifications.whatsapp_number'))
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', config('storefront.notifications.whatsapp_number')) }}" target="_blank" class="btn-outline text-sm w-full">WhatsApp Us</a>
                    @endif

                    @if (config('app.features.shipping_free_threshold'))
                        <div class="mt-8 p-4 bg-ivory-alt rounded-lg text-xs text-charcoal-soft">
                            <strong>Complimentary delivery</strong> on all orders. Receive within 2–4 business days via TCS / Leopards.
                        </div>
                    @endif
                </aside>
            </div>

            {{-- Reviews --}}
            @if ($product->allow_reviews && $product->approvedReviews->isNotEmpty())
                <section class="mt-16 pt-8 border-t border-charcoal/5">
                    <h2 class="font-serif text-2xl mb-6">Verified Reviews</h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        @foreach ($product->approvedReviews as $review)
                            <article class="p-5 bg-ivory-alt/30 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-champagne text-sm">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}</div>
                                    @if ($review->is_verified_purchase)
                                        <span class="badge bg-success/10 text-success text-[10px]">Verified</span>
                                    @endif
                                </div>
                                <h4 class="font-serif text-base mb-1">{{ $review->title ?? 'Haarsingharr customer' }}</h4>
                                <p class="text-sm text-charcoal-soft leading-relaxed">{{ $review->body }}</p>
                                <p class="text-xs text-muted mt-3">— {{ $review->customer_name ?? 'Anonymous' }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Related products --}}
            @if ($relatedProducts->isNotEmpty())
                <section class="mt-20">
                    <h2 class="font-serif text-2xl mb-6">You may also love</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                        @foreach ($relatedProducts as $related)
                            @include('storefront.product.card', ['product' => $related])
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
