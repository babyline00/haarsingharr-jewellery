@extends('layouts.storefront')

@section('title', 'Haarsingharr — Timeless Jewellery for Every You')

@section('content')
    {{-- Published hero banners rotate in a fixed display frame; the artwork is cropped with object-fit cover. --}}
    @php
        $heroSlides = $heroBanners->isNotEmpty() ? $heroBanners : collect([null]);
        $heroSlideCount = $heroSlides->count();
        $heroCanAutoplay = $heroAutoplay && $heroSlideCount > 1;
    @endphp
    <section
        class="relative isolate h-[min(72vh,680px)] min-h-[470px] max-h-[680px] overflow-hidden bg-[#21170f] md:min-h-[520px]"
        aria-label="Featured jewellery banners"
        x-data="{
            active: 0,
            paused: false,
            timer: null,
            count: {{ $heroSlideCount }},
            autoplay: @js($heroCanAutoplay),
            start() {
                if (!this.autoplay || this.count < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                this.timer = window.setInterval(() => { if (!this.paused) this.next(); }, {{ $heroAutoplaySeconds * 1000 }});
            },
            next() { this.active = (this.active + 1) % this.count; },
            previous() { this.active = (this.active + this.count - 1) % this.count; },
            goTo(index) { this.active = index; },
            togglePause() { this.paused = !this.paused; }
        }"
        x-init="start()"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        @focusin="paused = true"
        @focusout="if (!$el.contains($event.relatedTarget)) paused = false"
        @keydown.left.prevent="previous()"
        @keydown.right.prevent="next()"
        x-on:visibilitychange.window="paused = document.hidden"
    >
        @foreach ($heroSlides as $slideIndex => $slide)
            @php
                $isFallbackSlide = $slide === null;
                $slideImage = $isFallbackSlide ? asset('images/bridal-editorial-hero.webp') : \App\Support\Media::url($slide->image, \App\Support\Media::WIDE_PLACEHOLDER);
                $slideMobileImage = $isFallbackSlide ? $slideImage : \App\Support\Media::url($slide->mobile_image ?: $slide->image, \App\Support\Media::WIDE_PLACEHOLDER);
                $slideHeading = $isFallbackSlide ? 'Timeless elegance for every you' : ($slide->heading ?: 'Discover timeless jewellery');
                $slideSubheading = $isFallbackSlide ? 'Discover handcrafted jewellery that celebrates your unique story.' : ($slide->subheading ?: 'Handcrafted jewellery, made to be treasured.');
                $slideHref = $isFallbackSlide ? route('shop.new_arrivals') : ($slide->link_url ? route('banners.click', $slide) : route('shop.index'));
                $slideCta = $isFallbackSlide ? 'Shop new arrivals' : ($slide->link_text ?: 'Shop the collection');
                $slideAlt = $isFallbackSlide ? 'South Asian bride wearing handcrafted gold jewellery' : ($slide->alt_text ?: $slideHeading);
            @endphp
            <div class="absolute inset-0" x-show="active === {{ $slideIndex }}" x-transition.opacity.duration.500ms>
                <picture class="absolute inset-0">
                    @if (!$isFallbackSlide)
                        <source srcset="{{ $slideMobileImage }}" media="(max-width: 767px)">
                    @else
                        <source srcset="{{ $slideImage }}" type="image/webp">
                    @endif
                    <img src="{{ $isFallbackSlide ? asset('images/bridal-editorial-hero.png') : $slideImage }}" alt="{{ $slideAlt }}" class="h-full w-full object-cover object-[62%_center]" @if ($slideIndex === 0) fetchpriority="high" @else loading="lazy" @endif>
                </picture>
                <div class="absolute inset-0 bg-gradient-to-r from-[#1b130d]/90 via-[#1b130d]/55 to-transparent"></div>
                <div class="container-haars relative flex h-full items-center">
                    <div class="max-w-2xl px-2 py-16 md:px-10">
                        <p class="mb-4 text-[10px] font-semibold uppercase tracking-[.3em] text-[#f2d49e] md:text-xs">Timeless jewellery · Handcrafted with care</p>
                        <h1 class="max-w-xl font-serif text-5xl leading-[.98] text-white md:text-6xl lg:text-7xl">{{ $slideHeading }}</h1>
                        <p class="mt-5 max-w-sm text-sm leading-relaxed text-white/85 md:text-base">{{ $slideSubheading }}</p>
                        <div class="mt-7 flex flex-wrap gap-3">
                            <a href="{{ $slideHref }}" class="btn-gold">{{ $slideCta }} <span aria-hidden="true">→</span></a>
                            <a href="{{ route('shop.bridal') }}" class="inline-flex items-center rounded-full border border-white/50 px-5 py-3 text-sm text-white transition hover:bg-white/10">Explore bridal</a>
                        </div>
                        <div class="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-[11px] text-white/85 md:mt-9">
                            <span>♡ &nbsp;Hand-finished</span><span>◇ &nbsp;Thoughtful materials</span><span>◎ &nbsp;Secure checkout</span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if ($heroSlideCount > 1)
            <div class="absolute inset-x-0 bottom-4 z-10 flex items-center justify-center gap-3 md:bottom-6">
                <button type="button" @click="previous()" aria-label="Previous banner" class="grid h-9 w-9 place-items-center rounded-full border border-white/50 bg-black/20 text-white hover:bg-black/40">←</button>
                <div class="flex items-center gap-2" role="group" aria-label="Choose banner">
                    @foreach ($heroSlides as $slideIndex => $slide)
                        <button type="button" @click="goTo({{ $slideIndex }})" :aria-current="active === {{ $slideIndex }} ? 'true' : 'false'" aria-label="Show banner {{ $slideIndex + 1 }}" class="h-2.5 w-2.5 rounded-full border border-white/80 transition" :class="active === {{ $slideIndex }} ? 'bg-white' : 'bg-white/30'"></button>
                    @endforeach
                </div>
                <button type="button" @click="next()" aria-label="Next banner" class="grid h-9 w-9 place-items-center rounded-full border border-white/50 bg-black/20 text-white hover:bg-black/40">→</button>
                @if ($heroCanAutoplay)
                    <button type="button" @click="togglePause()" :aria-label="paused ? 'Resume banner rotation' : 'Pause banner rotation'" class="rounded-full border border-white/50 bg-black/20 px-3 py-2 text-xs text-white hover:bg-black/40" x-text="paused ? 'Play' : 'Pause'"></button>
                @endif
            </div>
        @endif
    </section>

    {{-- Category shortcuts use the store's active, homepage-enabled categories. --}}
    @if (isset($featuredCategories) && $featuredCategories->isNotEmpty())
        <section class="border-b border-[#8b5b2c]/10 bg-[#fffdf9] py-7 md:py-9">
            <div class="container-haars flex gap-4 overflow-x-auto pb-1 md:justify-between md:gap-5">
                @foreach ($featuredCategories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="group flex w-[88px] shrink-0 flex-col items-center text-center md:w-[104px]">
                        <span class="mb-2 block aspect-square w-[78px] overflow-hidden rounded-full bg-[#f2e5d4] ring-1 ring-[#a9773d]/15 md:w-[94px]">
                            @if ($category->banner_image)
                                <img src="{{ \App\Support\Media::url($category->banner_image) }}" alt="{{ $category->name }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-110" loading="lazy">
                            @else
                                <img src="{{ asset('images/placeholder-square.svg') }}" alt="" class="h-full w-full object-cover" loading="lazy">
                            @endif
                        </span>
                        <span class="font-serif text-sm text-[#30251b]">{{ $category->name }}</span>
                        <span class="mt-0.5 text-[11px] text-[#875323]">Shop now →</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Campaign tiles are editorial links; merchandise remains in the live product sections below. --}}
    <section class="container-haars py-6 md:py-8">
        <div class="grid gap-3 md:grid-cols-12" x-reveal>
            <a href="{{ route('shop.bridal') }}" class="group relative flex min-h-[205px] items-end overflow-hidden rounded-xl bg-[#3b291b] p-6 md:col-span-6 md:min-h-[230px] md:p-8">
                <img src="{{ asset('images/bridal-editorial-hero.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover object-[80%_center] opacity-75 transition duration-700 group-hover:scale-105" loading="lazy">
                <span class="absolute inset-0 bg-gradient-to-r from-[#24170d]/80 to-transparent"></span>
                <span class="relative text-white"><span class="block text-[10px] uppercase tracking-[.28em] text-[#f0d09d]">The festive edit</span><span class="mt-2 block max-w-sm font-serif text-3xl leading-none md:text-4xl">Tradition meets modern elegance</span><span class="mt-4 inline-flex rounded-full bg-[#efce8d] px-4 py-2 text-xs text-[#382311]">Shop bridal collection →</span></span>
            </a>
            <a href="{{ route('shop.new_arrivals') }}" class="group relative flex min-h-[150px] items-end overflow-hidden rounded-xl bg-[#efe1d0] p-5 md:col-span-3 md:min-h-[230px] md:p-6">
                <img src="{{ asset('images/bridal-editorial-hero.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover object-[72%_center] opacity-35 transition duration-700 group-hover:scale-105" loading="lazy">
                <span class="absolute inset-0 bg-gradient-to-t from-[#fbf3e8]/95 via-[#fbf3e8]/45 to-transparent"></span><span class="relative"><span class="font-serif text-2xl">New arrivals</span><span class="mt-1 block text-xs text-[#574638]">Discover the latest designs</span><span class="mt-3 block text-xs text-[#875323]">Explore new arrivals →</span></span>
            </a>
            <a href="{{ route('shop.index') }}" class="group relative flex min-h-[150px] items-end overflow-hidden rounded-xl bg-[#dfc8a9] p-5 md:col-span-3 md:min-h-[230px] md:p-6">
                <img src="{{ asset('images/bridal-editorial-hero.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover object-[55%_center] opacity-30 transition duration-700 group-hover:scale-105" loading="lazy">
                <span class="absolute inset-0 bg-gradient-to-t from-[#f8eee0]/95 via-[#f8eee0]/40 to-transparent"></span><span class="relative"><span class="font-serif text-2xl">A gift to treasure</span><span class="mt-1 block text-xs text-[#574638]">Find something unforgettable</span><span class="mt-3 block text-xs text-[#875323]">Browse the collection →</span></span>
            </a>
        </div>
    </section>

    {{-- Reels are managed as a published homepage section. Each item accepts a hosted video URL and optional poster. --}}
    @php($reelsSection = $sections->firstWhere('key', 'trending_reels'))
    @php($reelItems = collect(data_get($reelsSection?->configuration, 'items', []))->filter(fn ($item) => filled(data_get($item, 'video')))->take(8))
    @if ($reelsSection && $reelItems->isNotEmpty())
        <section class="container-haars py-6 md:py-8" aria-labelledby="homepage-reels-title">
            <div class="mb-4 flex items-end justify-between gap-4" x-reveal>
                <div>
                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-[.25em] text-[#9b692f]">A closer look</p>
                    <h2 id="homepage-reels-title" class="font-serif text-3xl md:text-4xl">{{ $reelsSection->title ?: 'Trending reels' }}</h2>
                    @if ($reelsSection->subtitle)<p class="mt-1 text-sm text-[#766859]">{{ $reelsSection->subtitle }}</p>@endif
                </div>
            </div>
            <div class="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-3 md:mx-0 md:grid md:grid-cols-4 md:gap-4 md:overflow-visible md:px-0 xl:grid-cols-6">
                @foreach ($reelItems as $item)
                    <article class="w-[min(68vw,230px)] shrink-0 snap-start md:w-auto">
                        <div class="relative aspect-[9/14] overflow-hidden rounded-xl bg-[#2b2118] shadow-sm">
                            <video class="h-full w-full object-cover" src="{{ data_get($item, 'video') }}" @if (data_get($item, 'poster')) poster="{{ data_get($item, 'poster') }}" @endif controls playsinline preload="none" aria-label="{{ data_get($item, 'title', 'Jewellery reel') }}"></video>
                        </div>
                        @if (data_get($item, 'title'))<h3 class="mt-2 line-clamp-1 font-serif text-sm text-[#30251b]">{{ data_get($item, 'title') }}</h3>@endif
                        @if (data_get($item, 'views'))<p class="text-xs text-[#766859]">{{ data_get($item, 'views') }} views</p>@endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Best sellers --}}
    @if (isset($bestSellers) && $bestSellers->isNotEmpty())
        <section class="container-haars py-5 md:py-8">
            <div class="mb-5 flex items-end justify-between" x-reveal>
                <div><p class="mb-1 text-[10px] font-semibold uppercase tracking-[.25em] text-[#9b692f]">Loved by our customers</p><h2 class="font-serif text-3xl md:text-4xl">Trending pieces</h2></div>
                <a href="{{ route('shop.best_sellers') }}" class="pb-1 text-xs text-[#754318] hover:underline">View all →</a>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5 xl:grid-cols-4" x-reveal="80">
                @foreach ($bestSellers->take(8) as $product)
                    @include('storefront.product.card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    {{-- New arrivals --}}
    @if (isset($newArrivals) && $newArrivals->isNotEmpty())
        <section class="bg-[#f8f3ec] py-7 md:py-10">
            <div class="container-haars">
                <div class="mb-5 flex items-end justify-between" x-reveal><div><p class="mb-1 text-[10px] font-semibold uppercase tracking-[.25em] text-[#9b692f]">Just arrived</p><h2 class="font-serif text-3xl md:text-4xl">Fresh from the atelier</h2></div><a href="{{ route('shop.new_arrivals') }}" class="pb-1 text-xs text-[#754318] hover:underline">View all →</a></div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5" x-reveal="80">
                    @foreach ($newArrivals->take(8) as $product)
                        @include('storefront.product.card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- More live storefront collections --}}
    @if (isset($bridalCollection) && $bridalCollection->isNotEmpty())
        <section class="container-haars py-8 md:py-12">
            <a href="{{ route('shop.bridal') }}" class="group relative mb-7 flex min-h-[220px] items-end overflow-hidden rounded-xl bg-[#261b13] p-6 md:min-h-[290px] md:p-10" x-reveal>
                <img src="{{ asset('images/bridal-editorial-hero.webp') }}" alt="Bridal jewellery collection" class="absolute inset-0 h-full w-full object-cover object-[70%_center] transition duration-700 group-hover:scale-[1.02]" loading="lazy"><span class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/35 to-transparent"></span>
                <span class="relative text-white"><span class="block text-[10px] uppercase tracking-[.28em] text-[#f1d49e]">The bridal atelier</span><span class="mt-2 block max-w-lg font-serif text-4xl leading-tight md:text-5xl">Heritage in every detail</span><span class="mt-4 inline-flex rounded-full bg-[#efce8d] px-5 py-2.5 text-sm text-[#382311]">Explore the bridal edit →</span></span>
            </a>
            <div class="mb-5 flex items-end justify-between" x-reveal><div><p class="mb-1 text-[10px] font-semibold uppercase tracking-[.25em] text-[#9b692f]">Made for the moment</p><h2 class="font-serif text-3xl">Bridal favourites</h2></div><a href="{{ route('shop.bridal') }}" class="text-xs text-[#754318] hover:underline">View bridal →</a></div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5" x-reveal="80">@foreach ($bridalCollection->take(4) as $product) @include('storefront.product.card', ['product' => $product]) @endforeach</div>
        </section>
    @endif

    @if (isset($saleItems) && $saleItems->isNotEmpty())
        <section class="container-haars py-6 md:py-9"><div class="mb-5 flex items-end justify-between" x-reveal><div><p class="mb-1 text-[10px] font-semibold uppercase tracking-[.25em] text-[#9b692f]">Limited-time favourites</p><h2 class="font-serif text-3xl">Special finds</h2></div><a href="{{ route('shop.sale') }}" class="text-xs text-[#754318] hover:underline">Shop all →</a></div><div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5" x-reveal="80">@foreach ($saleItems->take(4) as $product) @include('storefront.product.card', ['product' => $product]) @endforeach</div></section>
    @endif

    {{-- Editorial services --}}
    @php($serviceSection = $sections->firstWhere('key', 'service_highlights'))
    <section class="border-y border-[#8b5b2c]/10 bg-[#fbf7f0] py-6 md:py-8">
        <div class="container-haars">@if ($serviceSection && $serviceSection->content)<div class="text-charcoal-soft leading-relaxed">{!! $serviceSection->content !!}</div>@else
            <div class="grid grid-cols-2 gap-5 md:grid-cols-4 md:gap-8">
                <div class="flex items-center gap-3"><span class="text-3xl text-[#9a6327]">♧</span><span><b class="block font-serif text-sm">Careful delivery</b><small class="text-xs text-[#766859]">Across Pakistan</small></span></div>
                <div class="flex items-center gap-3"><span class="text-3xl text-[#9a6327]">◇</span><span><b class="block font-serif text-sm">Easy exchanges</b><small class="text-xs text-[#766859]">7-day return policy</small></span></div>
                <div class="flex items-center gap-3"><span class="text-3xl text-[#9a6327]">⌑</span><span><b class="block font-serif text-sm">Secure payments</b><small class="text-xs text-[#766859]">Protected checkout</small></span></div>
                <div class="flex items-center gap-3"><span class="text-3xl text-[#9a6327]">◎</span><span><b class="block font-serif text-sm">Worldwide shipping</b><small class="text-xs text-[#766859]">Delight delivered globally</small></span></div>
            </div>@endif
        </div>
    </section>

    @php($testimonialsSection = $sections->firstWhere('key', 'testimonials'))
    <section class="bg-[#2b2118] py-10 text-[#fffaf2] md:py-14"><div class="container-haars"><div class="mb-7 text-center"><p class="mb-1 text-[10px] uppercase tracking-[.28em] text-[#e4c38e]">Worn with love</p><h2 class="font-serif text-3xl md:text-4xl">From our patrons</h2></div>
        @if ($testimonialsSection && $testimonialsSection->content)<div class="text-white/80 leading-relaxed">{!! $testimonialsSection->content !!}</div>@else
            <div class="grid gap-7 md:grid-cols-3 md:gap-10"><div class="text-center"><div class="mb-2 text-xl text-[#e4c38e]">★★★★★</div><p class="text-sm italic leading-relaxed text-white/80">“The engagement ring is beautiful. The way the stones catch the light is something else.”</p><p class="mt-3 text-xs text-[#e4c38e]">— Ayesha, Karachi</p></div><div class="text-center"><div class="mb-2 text-xl text-[#e4c38e]">★★★★★</div><p class="text-sm italic leading-relaxed text-white/80">“The bangles I chose for my mother are absolutely stunning. A lovely experience from start to finish.”</p><p class="mt-3 text-xs text-[#e4c38e]">— Sana, Lahore</p></div><div class="text-center"><div class="mb-2 text-xl text-[#e4c38e]">★★★★★</div><p class="text-sm italic leading-relaxed text-white/80">“Thoughtful craftsmanship and a timeless design. I will treasure this piece for years.”</p><p class="mt-3 text-xs text-[#e4c38e]">— Imran, Islamabad</p></div></div>
        @endif
    </div></section>

    @if (isset($storeLocations) && $storeLocations->isNotEmpty())
        <section class="container-haars py-10 md:py-14"><div class="mb-7 text-center" x-reveal><p class="mb-1 text-[10px] uppercase tracking-[.25em] text-[#9b692f]">Visit Haarsingharr</p><h2 class="font-serif text-3xl md:text-4xl">Our boutiques</h2></div><div class="grid gap-4 md:grid-cols-3" x-reveal="80">@foreach ($storeLocations as $location)<div class="rounded-xl border border-[#8b5b2c]/15 bg-white p-5 text-center"><h3 class="font-serif text-xl">{{ $location->name }}</h3><p class="mt-2 text-sm text-[#766859]">{{ $location->address }}</p>@if ($location->phone)<a href="tel:{{ $location->phone }}" class="mt-2 block text-sm text-[#754318]">{{ $location->phone }}</a>@endif @if ($location->hours)<p class="mt-2 text-xs text-[#766859]">{{ $location->hours }}</p>@endif</div>@endforeach</div><div class="mt-6 text-center"><a href="{{ route('locations') }}" class="btn-outline">All store locations</a></div></section>
    @endif
@endsection
