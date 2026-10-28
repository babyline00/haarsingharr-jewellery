@extends('layouts.storefront')

@section('title', 'Haarsingharr — Timeless Jewellery for Every You')

@section('content')
<<<<<<< HEAD
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
=======
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
>>>>>>> origin/main
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

<<<<<<< HEAD
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
=======
    {{-- Live store stats --}}
    @if (isset($homeStats))
        <section class="border-y border-charcoal/10 bg-ivory-alt/30">
            <div class="container-haars grid grid-cols-2 divide-x divide-charcoal/10 md:grid-cols-4">
                <div class="px-4 py-5 text-center">
                    <p class="font-serif text-2xl md:text-3xl">{{ number_format($homeStats['products']) }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-muted">Pieces in store</p>
                </div>
                <div class="px-4 py-5 text-center">
                    <p class="font-serif text-2xl md:text-3xl">{{ number_format($homeStats['customers']) }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-muted">Happy customers</p>
                </div>
                <div class="px-4 py-5 text-center">
                    <p class="font-serif text-2xl md:text-3xl">{{ number_format($homeStats['orders']) }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-muted">Orders delivered</p>
                </div>
                <div class="px-4 py-5 text-center">
                    <p class="font-serif text-2xl md:text-3xl">{{ number_format($homeStats['views']) }}</p>
                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-muted">Product views</p>
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
>>>>>>> origin/main
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
<<<<<<< HEAD
        <section class="container-haars py-5 md:py-8">
            <div class="mb-5 flex items-end justify-between" x-reveal>
                <div><p class="mb-1 text-[10px] font-semibold uppercase tracking-[.25em] text-[#9b692f]">Loved by our customers</p><h2 class="font-serif text-3xl md:text-4xl">Trending pieces</h2></div>
                <a href="{{ route('shop.best_sellers') }}" class="pb-1 text-xs text-[#754318] hover:underline">View all →</a>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5 xl:grid-cols-4" x-reveal="80">
                @foreach ($bestSellers->take(8) as $product)
                    @include('storefront.product.card', ['product' => $product])
                @endforeach
=======
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
>>>>>>> origin/main
            </div>
        </section>
    @endif

    {{-- New arrivals --}}
    @if (isset($newArrivals) && $newArrivals->isNotEmpty())
<<<<<<< HEAD
        <section class="bg-[#f8f3ec] py-7 md:py-10">
            <div class="container-haars">
                <div class="mb-5 flex items-end justify-between" x-reveal><div><p class="mb-1 text-[10px] font-semibold uppercase tracking-[.25em] text-[#9b692f]">Just arrived</p><h2 class="font-serif text-3xl md:text-4xl">Fresh from the atelier</h2></div><a href="{{ route('shop.new_arrivals') }}" class="pb-1 text-xs text-[#754318] hover:underline">View all →</a></div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-5" x-reveal="80">
                    @foreach ($newArrivals->take(8) as $product)
=======
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
>>>>>>> origin/main
                        @include('storefront.product.card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

<<<<<<< HEAD
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
=======
    {{-- Trending now — ranked 1–4 by real views/sales --}}
    @if (isset($trending) && $trending->isNotEmpty())
        <section class="section py-12 md:py-16">
            <div class="container-haars">
                <div class="flex items-end justify-between mb-10">
                    <div>
                        <p class="text-champagne-deep text-xs font-semibold tracking-[0.3em] uppercase mb-2">Most Viewed</p>
                        <h2 class="font-serif text-3xl md:text-4xl">Trending Now</h2>
                    </div>
                    <a href="{{ route('shop.index') }}" class="text-sm text-charcoal-soft hover:text-champagne transition-colors">Shop all →</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @foreach ($trending as $product)
                        <div class="relative">
                            <span class="absolute -left-2 -top-2 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-charcoal font-serif text-sm text-ivory shadow-lg ring-2 ring-champagne/50" title="Rank {{ $loop->iteration }}">
                                {{ $loop->iteration }}
                            </span>
                            @include('storefront.product.card', ['product' => $product])
                        </div>
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
>>>>>>> origin/main

    @if (isset($storeLocations) && $storeLocations->isNotEmpty())
<<<<<<< HEAD
        <section class="container-haars py-10 md:py-14"><div class="mb-7 text-center" x-reveal><p class="mb-1 text-[10px] uppercase tracking-[.25em] text-[#9b692f]">Visit Haarsingharr</p><h2 class="font-serif text-3xl md:text-4xl">Our boutiques</h2></div><div class="grid gap-4 md:grid-cols-3" x-reveal="80">@foreach ($storeLocations as $location)<div class="rounded-xl border border-[#8b5b2c]/15 bg-white p-5 text-center"><h3 class="font-serif text-xl">{{ $location->name }}</h3><p class="mt-2 text-sm text-[#766859]">{{ $location->address }}</p>@if ($location->phone)<a href="tel:{{ $location->phone }}" class="mt-2 block text-sm text-[#754318]">{{ $location->phone }}</a>@endif @if ($location->hours)<p class="mt-2 text-xs text-[#766859]">{{ $location->hours }}</p>@endif</div>@endforeach</div><div class="mt-6 text-center"><a href="{{ route('locations') }}" class="btn-outline">All store locations</a></div></section>
=======
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
>>>>>>> origin/main
    @endif
    {{-- Scratch & win gift popup (admin-configurable) --}}
    @include('storefront.home.partials.gift-popup')
@endsection
