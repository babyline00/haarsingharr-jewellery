@extends('layouts.storefront')

@section('title', $pageTitle ?? 'Shop All — Haarsingharr')

@section('content')
    <section class="section" x-data="{ filtersOpen: false }">
        <div class="container-haars">

            {{-- Page header --}}
            <div class="mb-8 md:mb-12" x-reveal>
                <h1 class="font-serif text-3xl md:text-4xl lg:text-5xl">{{ $pageTitle ?? 'All Jewellery' }}</h1>
                <p class="text-base text-muted mt-2">{{ $products->total() }} pieces — hand-crafted, hallmarked 22k gold.</p>
            </div>

            {{-- Mobile filter toggle --}}
            <div class="lg:hidden mb-6">
                <button type="button" @click="filtersOpen = !filtersOpen" class="btn-outline w-full flex items-center justify-between" aria-expanded="false" :aria-expanded="filtersOpen.toString()" aria-controls="mobile-filters">
                    <span class="flex items-center gap-2">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/><circle cx="8" cy="6" r="2" fill="currentColor"/><circle cx="16" cy="12" r="2" fill="currentColor"/><circle cx="10" cy="18" r="2" fill="currentColor"/></svg>
                        Filters & Sorting
                    </span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="transition-transform duration-200" :class="{ 'rotate-180': filtersOpen }"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
            </div>

            <div class="grid lg:grid-cols-[260px_1fr] gap-8 lg:gap-10">

                {{-- Filters --}}
                <aside class="hidden lg:block" :class="{ '!block': filtersOpen }" id="mobile-filters">
                    <div class="sticky top-24 space-y-6 rounded-xl border border-charcoal/8 bg-porcelain p-5">

                        {{-- Categories --}}
                        <div>
                            <h3 class="font-serif text-sm uppercase tracking-widest text-charcoal-soft mb-3">Categories</h3>
                            <ul class="space-y-1.5 text-sm">
                                @foreach ($categories as $category)
                                    <li>
                                        <a href="{{ route('categories.show', $category) }}"
                                           class="block text-charcoal-soft hover:text-champagne transition-colors px-3 py-1.5 rounded-md @if (request('category') === $category->slug) text-champagne-dark font-medium bg-champagne/10 @endif transition-all duration-200">
                                            {{ $category->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Price range --}}
                        <div>
                            <h3 class="font-serif text-sm uppercase tracking-widest text-charcoal-soft mb-3">Price</h3>
                            <form method="get" action="{{ route('shop.index') }}" class="space-y-3">
                                <div class="flex gap-2">
                                    <input type="number" name="min" placeholder="Min" value="{{ request('min') }}" class="text-sm w-full" min="0" step="1000">
                                    <input type="number" name="max" placeholder="Max" value="{{ request('max') }}" class="text-sm w-full" min="0" step="1000">
                                </div>
                                <button type="submit" class="btn-outline text-sm w-full py-2">Apply</button>
                            </form>
                        </div>

                        {{-- Collections --}}
                        @if ($collections->isNotEmpty())
                            <div>
                                <h3 class="font-serif text-sm uppercase tracking-widest text-charcoal-soft mb-3">Collections</h3>
                                <ul class="space-y-1.5 text-sm">
                                    @foreach ($collections as $collection)
                                        <li>
                                            <a href="{{ route('collections.show', $collection) }}" class="block text-charcoal-soft hover:text-champagne transition-colors px-3 py-1.5 rounded-md hover:bg-ivory-alt/50">{{ $collection->name }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Budget shortcuts --}}
                        <div>
                            <h3 class="font-serif text-sm uppercase tracking-widest text-charcoal-soft mb-3">Shop by Budget</h3>
                            <ul class="space-y-1.5 text-sm">
                                <li><a href="{{ route('shop.budget', 'under-15k') }}" class="text-charcoal-soft hover:text-champagne transition-colors block px-3 py-1.5 rounded-md hover:bg-ivory-alt/50">Under Rs. 15k</a></li>
                                <li><a href="{{ route('shop.budget', '15k-to-50k') }}" class="text-charcoal-soft hover:text-champagne transition-colors block px-3 py-1.5 rounded-md hover:bg-ivory-alt/50">Rs. 15k – 50k</a></li>
                                <li><a href="{{ route('shop.budget', '50k-to-150k') }}" class="text-charcoal-soft hover:text-champagne transition-colors block px-3 py-1.5 rounded-md hover:bg-ivory-alt/50">Rs. 50k – 150k</a></li>
                                <li><a href="{{ route('shop.budget', 'above-150k') }}" class="text-charcoal-soft hover:text-champagne transition-colors block px-3 py-1.5 rounded-md hover:bg-ivory-alt/50">Above Rs. 150k</a></li>
                            </ul>
                        </div>
                    </div>
                </aside>

                {{-- Products grid --}}
                <div>
                    {{-- Toolbar --}}
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
                        <form method="get" action="{{ route('shop.index') }}" class="flex flex-col sm:flex-row items-start sm:items-center gap-2 w-full sm:w-auto">
                            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search within results…" class="text-sm w-full sm:w-56 md:w-72 lg:w-80">
                            <button type="submit" class="btn-outline text-sm py-2 shrink-0">Search</button>
                        </form>
                        <form method="get" action="{{ request()->fullUrlWithoutQuery('page') }}" class="w-full sm:w-auto">
                            <select name="sort" class="text-sm w-full sm:w-auto max-w-[180px]" onchange="this.form.submit()">
                                <option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest</option>
                                <option value="popular" @selected(($filters['sort'] ?? '') === 'popular')>Most Popular</option>
                                <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Price: Low → High</option>
                                <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Price: High → Low</option>
                            </select>
                            @foreach (['q', 'category', 'min', 'max'] as $param)
                                @if (request($param)) <input type="hidden" name="{{ $param }}" value="{{ request($param) }}"> @endif
                            @endforeach
                        </form>
                    </div>

                    @if ($products->isNotEmpty())
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 xl:gap-8" x-reveal="80">
                            @foreach ($products as $product)
                                @include('storefront.product.card', ['product' => $product])
                            @endforeach
                        </div>

                        <div class="mt-10">
                            {{ $products->withQueryString()->links() }}
                        </div>
                    @else
                        <div class="text-center py-16 md:py-20">
                            <svg class="w-16 h-16 mx-auto text-muted/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8-4v10l-8 4m0-10L4 7m8-4v10M4 7v10l8 4"/></svg>
                            <p class="font-serif text-2xl md:text-3xl text-muted mb-3">
                                @if (($query ?? '') !== '') No pieces found for “{{ $query }}” @else No pieces found @endif
                            </p>
                            <p class="text-sm text-muted mb-6 max-w-xs mx-auto">Try adjusting your filters or explore our bridal edit.</p>
                            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                                <a href="{{ route('shop.bridal') }}" class="btn-outline mt-4">Bridal Edit</a>
                                <a href="{{ route('shop.index') }}" class="btn-gold mt-4">Browse All</a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
