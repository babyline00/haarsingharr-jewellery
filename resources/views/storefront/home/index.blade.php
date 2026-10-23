@extends('layouts.storefront')

@section('title', 'Haarsingharr.pk — Premium Jewellery, Jewelry For Every You')

@section('content')
    {{-- Hero banner --}}
    @if ($heroBanner)
        <section class="relative h-[60vh] md:h-[88vh] overflow-hidden">
            <picture>
                <source srcset="{{ \App\Support\Media::url($heroBanner->mobile_image ?? $heroBanner->image, \App\Support\Media::WIDE_PLACEHOLDER) }}" media="(max-width: 768px)">
                <img src="{{ \App\Support\Media::url($heroBanner->image, \App\Support\Media::WIDE_PLACEHOLDER) }}"
                     alt="{{ $heroBanner->heading ?? 'Haarsingharr.pk Collection' }}"
                     class="absolute inset-0 w-full h-full object-cover object-center" loading="eager" fetchpriority="high">
            </picture>
            <div class="absolute inset-0 bg-gradient-to-t from-charcoal/60 via-charcoal/10 to-charcoal/30"></div>
            <div class="relative h-full container-haars flex flex-col items-start justify-end pb-16 md:pb-24">
                <p class="text-champagne text-xs md:text-sm font-semibold tracking-[0.3em] uppercase mb-3">{{ $heroBanner->subheading ?? 'Jewelry • For Every You' }}</p>
                <h1 class="font-serif text-ivory text-4xl md:text-6xl lg:text-7xl text-balance leading-[1.05] max-w-2xl">
                    {{ $heroBanner->heading ?? 'Jewelry For Every You' }}
                </h1>
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <a href="{{ $heroBanner->link_url ?? route('shop.index') }}" class="btn-gold">
                        {{ $heroBanner->link_text ?? 'Discover the Collection' }}
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                    <a href="{{ route('shop.bridal') }}" class="btn-outline border-ivory/30 text-ivory hover:bg-ivory/10 hover:border-ivory">
                        Bridal Edit
                    </a>
                </div>
            </div>
        </section>
    @else
        {{-- Fallback hero using the provided Haarsingharr.pk banner image --}}
        <section class="relative h-[60vh] md:h-[80vh] overflow-hidden bg-charcoal">
            <img src="{{ asset('images/hero-banner.png') }}"
                 alt="Haarsingharr.pk — Jewelry For Every You"
                 class="absolute inset-0 w-full h-full object-cover object-center opacity-90" loading="eager" fetchpriority="high">
            <div class="absolute inset-0 bg-gradient-to-t from-charcoal/80 via-charcoal/20 to-transparent"></div>
            <div class="relative h-full container-haars flex flex-col items-start justify-end pb-16 md:pb-24">
                <p class="text-champagne text-xs md:text-sm font-semibold tracking-[0.3em] uppercase mb-3">Jewelry • For Every You</p>
                <h1 class="font-serif text-ivory text-4xl md:text-6xl lg:text-7xl text-balance leading-[1.05] max-w-2xl">
                    Hand-crafted jewellery,<br>made for every you.
                </h1>
                <div class="mt-6 flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('shop.index') }}" class="btn-gold">
                        Discover the Collection
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                    <a href="{{ route('shop.bridal') }}" class="btn-outline border-ivory/30 text-ivory hover:bg-ivory/10 hover:border-ivory">
                        Bridal Edit
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- Featured collections --}}
    @if (isset($featuredCategories) && $featuredCategories->isNotEmpty())
        <section class="section">
            <div class="container-haars">
                <div class="flex items-end justify-between mb-10">
                    <div>
                        <p class="text-champagne-deep text-xs font-semibold tracking-[0.3em] uppercase mb-2">Curated by Haarsingharr</p>
                        <h2 class="font-serif text-3xl md:text-4xl">Explore Collections</h2>
                    </div>
                    <a href="{{ route('shop.index') }}" class="text-sm text-charcoal-soft hover:text-champagne transition-colors hidden md:flex items-center gap-1">
                        View all <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </a>
                </div>
            <div class="relative" x-data="{
                current: 0,
                total: {{ $featuredCategories->count() }},
                perPage: 4,
                get maxSlide() { return Math.max(0, this.total - this.perPage); },
                init() {
                    this.updatePerPage();
                    window.addEventListener('resize', () => this.updatePerPage());
                    if (this.maxSlide > 0) {
                        setInterval(() => { this.next(); }, 4000);
                    }
                },
                updatePerPage() {
                    if (window.innerWidth < 640) this.perPage = 2;
                    else if (window.innerWidth < 1024) this.perPage = 3;
                    else this.perPage = 4;
                },
                next() {
                    this.current = this.current >= this.maxSlide ? 0 : this.current + 1;
                },
                prev() {
                    this.current = this.current <= 0 ? this.maxSlide : this.current - 1;
                },
                goTo(i) { this.current = i; }
            }">
                <div class="overflow-hidden -mx-2">
                    <div class="flex transition-transform duration-500 ease-out" :style="'transform: translateX(-' + (current * (100 / perPage)) + '%)'">
                        @foreach ($featuredCategories as $category)
                            <div class="flex-shrink-0 px-2" :style="'width: ' + (100 / perPage) + '%'">
                                <a href="{{ route('categories.show', $category) }}" class="card card-hover group block relative aspect-[3/4] overflow-hidden rounded-2xl">
                                    @if ($category->banner_image)
                                        <img src="{{ \App\Support\Media::url($category->banner_image) }}" alt="{{ $category->name }}" class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                    @else
                                        <div class="absolute inset-0 bg-gradient-to-br from-ivory-alt to-champagne/20"></div>
                                    @endif
                                    <div class="absolute inset-0 bg-gradient-to-t from-charcoal/70 via-transparent to-transparent"></div>
                                    <div class="absolute bottom-4 left-4 right-4 text-ivory">
                                        <h3 class="font-serif text-lg md:text-xl">{{ $category->name }}</h3>
                                        <p class="text-xs text-ivory/80 mt-0.5 line-clamp-1">{{ $category->short_description }}</p>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Navigation dots --}}
                <div class="flex justify-center gap-2 mt-6" x-show="maxSlide > 0" x-cloak>
                    <template x-for="i in (maxSlide + 1)" :key="i">
                        <button @click="goTo(i-1)" class="w-2.5 h-2.5 rounded-full transition-all duration-300" :class="current === i-1 ? 'bg-champagne w-6' : 'bg-charcoal/20 hover:bg-charcoal/40'" :aria-label="'Go to slide ' + i"></button>
                    </template>
                </div>

                {{-- Navigation arrows --}}
                <button @click="prev()" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-porcelain/90 hover:bg-porcelain rounded-full flex items-center justify-center text-charcoal-soft hover:text-charcoal shadow-md transition-all z-10" x-show="maxSlide > 0" x-cloak aria-label="Previous slide">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
                <button @click="next()" class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 bg-porcelain/90 hover:bg-porcelain rounded-full flex items-center justify-center text-charcoal-soft hover:text-charcoal shadow-md transition-all z-10" x-show="maxSlide > 0" x-cloak aria-label="Next slide">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
            </div>
            </div>
        </section>
    @endif

    {{-- Best sellers --}}
    @if (isset($bestSellers) && $bestSellers->isNotEmpty())
        <section class="section py-12 md:py-16 p-5 bg-ivory-alt/40">
            <div class="container-haars">
                <div class="text-center mb-10">
                    <p class="text-champagne-deep text-xs font-semibold tracking-[0.3em] uppercase mb-2">Most Loved</p>
                    <h2 class="font-serif text-3xl md:text-4xl">Bestsellers</h2>
                    <div class="divider-gold w-16 mx-auto mt-4"></div>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @foreach ($bestSellers as $product)
                        @include('storefront.product.card', ['product' => $product])
                    @endforeach
                </div>
                <div class="text-center mt-10">
                    <a href="{{ route('shop.best_sellers') }}" class="btn-outline">View all bestsellers</a>
                </div>
            </div>
        </section>
    @endif

    {{-- New arrivals --}}
    @if (isset($newArrivals) && $newArrivals->isNotEmpty())
        <section class="section py-12 md:py-16 p-6">
            <div class="container-haars">
                <div class="flex items-end justify-between mb-10">
                    <div>
                        <p class="text-champagne-deep text-xs font-semibold tracking-[0.3em] uppercase mb-2">Just Arrived</p>
                        <h2 class="font-serif text-3xl md:text-4xl">New Arrivals</h2>
                    </div>
                    <a href="{{ route('shop.new_arrivals') }}" class="text text-charcoal-soft hover:text-champagne transition-colors">View all →</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @foreach ($newArrivals as $product)
                        @include('storefront.product.card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Bridal banner --}}
    @if (isset($bridalCollection) && $bridalCollection->isNotEmpty())
        <section class="relative py-12 md:py-16 bg-plum overflow-hidden">
            <div class="container-haars relative">
                <div class="grid md:grid-cols-2 p-5 gap-8 items-center">
                    <div class="text-ivory">
                        <p class="text-champagne text-xs font-semibold tracking-[0.3em] uppercase mb-3">The Bridal Atelier</p>
                        <h2 class="font-serif text-4xl md:text-5xl text-balance leading-[1.05]">Crafted for the moments that last a lifetime.</h2>
                        <p class="mt-5 text-ivory/70 max-w-md leading-relaxed">
                            Hand-finished 22k gold, polki diamonds, and Kashmiri emeralds — each piece composed
                            by our master artisans to celebrate the bride and her story.
                        </p>
                        <a href="{{ route('shop.bridal') }}" class="btn-gold mt-7">Explore the Bridal Edit</a>
                    </div>
                    <div class="relative">
                        @foreach ($bridalCollection->take(2) as $item)
                            <a href="{{ route('products.show', $item) }}" class="block {{ $loop->first ? 'relative' : 'absolute bottom-0 right-0 w-1/2 shadow-2xl' }}">
                                <img src="{{ \App\Support\Media::url($item->primaryImageCardPath()) }}" alt="{{ $item->name }}" class="rounded-lg w-full aspect-square object-cover">
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Shop by budget --}}
    @if (isset($budgetCategories))
        <section class="section py-12 md:py-16 ">
            <div class="container-haars">
                <div class="text-center mb-10">
                    <p class="text-champagne-deep text-xs font-semibold tracking-[0.3em] uppercase mb-2">For Every Milestone</p>
                    <h2 class="font-serif text-3xl md:text-4xl">Shop by Budget</h2>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @foreach ($budgetCategories as $range)
                        <a href="{{ route('shop.budget', \Illuminate\Support\Str::slug($range['label'])) }}" class="card card-hover p-6 text-center group">
                            <div class="font-serif text-2xl md:text-3xl text-charcoal mb-2 group-hover:text-champagne-dark transition-colors">{{ $range['label'] }}</div>
                            <div class="text-xs text-muted">Curated picks</div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @php $testimonialsSection = $sections->firstWhere('key', 'testimonials'); @endphp
    <section class="section py-12 md:py-16 bg-charcoal text-ivory">
        <div class="container-haars">
            <div class="text-center mb-10">
                <p class="text-champagne text-xs font-semibold tracking-[0.3em] uppercase mb-2">Worn with Love</p>
                <h2 class="font-serif text-3xl md:text-4xl">From Our Patrons</h2>
            </div>
            @if ($testimonialsSection && $testimonialsSection->content)
                <div class="text-ivory/80 leading-relaxed">
                    {!! $testimonialsSection->content !!}
                </div>
            @else
                <div class="grid md:grid-cols-3 gap-6 md:gap-10">
                    <div class="text-center">
                        <div class="text-champagne text-3xl mb-2">★★★★★</div>
                        <p class="text-ivory/80 italic leading-relaxed">"Haarsingharr designed our engagement ring — the way the diamond catches light is something else. Worth every rupee."</p>
                        <p class="mt-3 text-sm text-champagne">— Ayesha & Bilal, Karachi</p>
                    </div>
                    <div class="text-center">
                        <div class="text-champagne text-3xl mb-2">★★★★★</div>
                        <p class="text-ivory/80 italic leading-relaxed">"The bangles I bought for my mother's 60th are absolutely stunning. The team arranged doorstep delivery in Lahore the next day."</p>
                        <p class="mt-3 text-sm text-champagne">— Sana M., Lahore</p>
                    </div>
                    <div class="text-center">
                        <div class="text-champagne text-3xl mb-2">★★★★★</div>
                        <p class="text-ivory/80 italic leading-relaxed">"I've bought from many jewellers — Haarsingharr's 22k polish is the most consistent I've seen. A proper heirloom brand."</p>
                        <p class="mt-3 text-sm text-champagne">— Imran S., Islamabad</p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Service highlights --}}
    @php $serviceSection = $sections->firstWhere('key', 'service_highlights'); @endphp
    <section class="py-12 md:py-16 bg-ivory-alt/30">
        <div class="container-haars">
            @if ($serviceSection && $serviceSection->content)
                <div class="text-charcoal-soft leading-relaxed">
                    {!! $serviceSection->content !!}
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-10">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-champagne/10 flex items-center justify-center mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-champagne-dark"><rect x="1" y="3" width="15" height="13"/><line x1="16" y1="8" x2="20" y2="8"/><line x1="23" y1="3" x2="23" y2="16"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        </div>
                        <h3 class="font-serif text-base">Complimentary Delivery</h3>
                        <p class="text-xs text-muted mt-1">On every order, nationwide.</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-champagne/10 flex items-center justify-center mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-champagne-dark"><path d="M3 12a9 9 0 1 0 9-9 9.7 9.7 0 0 0-6 2L3 3v6"/></svg>
                        </div>
                        <h3 class="font-serif text-base">7-Day Exchange</h3>
                        <p class="text-xs text-muted mt-1">No-questions exchange on unworn pieces.</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-champagne/10 flex items-center justify-center mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-champagne-dark"><path d="M12 2L3 6v6c0 5 4 9 9 10 5-1 9-5 9-10V6l-9-4z"/></svg>
                        </div>
                        <h3 class="font-serif text-base">Hallmarked 22k Gold</h3>
                        <p class="text-xs text-muted mt-1">Certified purity on every piece.</p>
                    </div>
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto rounded-full bg-champagne/10 flex items-center justify-center mb-3">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-champagne-dark"><path d="M21 11.5a8.4 8.4 0 0 1-12 7.6L4 22l1.9-5A8.4 8.4 0 1 1 21 11.5z"/></svg>
                        </div>
                        <h3 class="font-serif text-base">Private Consultations</h3>
                        <p class="text-xs text-muted mt-1">In-store or via WhatsApp, by appointment.</p>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Store locations --}}
    @if (isset($storeLocations) && $storeLocations->isNotEmpty())
        <section class="section py-12 md:py-16 ">
            <div class="container-haars">
                <div class="text-center mb-10">
                    <p class="text-champagne-deep text-xs font-semibold tracking-[0.3em] uppercase mb-2">Visit Haarsingharr</p>
                    <h2 class="font-serif text-3xl md:text-4xl">Our Boutiques</h2>
                </div>
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach ($storeLocations as $location)
                        <div class="card p-6 text-center">
                            <h3 class="font-serif text-xl">{{ $location->name }}</h3>
                            <p class="text-sm text-muted mt-2">{{ $location->address }}</p>
                            @if ($location->phone)
                                <a href="tel:{{ $location->phone }}" class="block text-sm text-charcoal-soft mt-2 hover:text-champagne transition-colors">{{ $location->phone }}</a>
                            @endif
                            @if ($location->hours)
                                <p class="text-xs text-muted mt-2">{{ $location->hours }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="text-center mt-8">
                    <a href="{{ route('locations') }}" class="btn-outline">All store locations</a>
                </div>
            </div>
        </section>
    @endif
@endsection
