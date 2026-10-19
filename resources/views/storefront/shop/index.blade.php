@extends('layouts.storefront')

@section('title', $pageTitle ?? 'Shop All — Haarsingharr')

@section('content')
    <section class="section">
        <div class="container-haars">

            {{-- Page header --}}
            <div class="mb-8 md:mb-12">
                <h1 class="font-serif text-3xl md:text-4xl">{{ $pageTitle ?? 'All Jewellery' }}</h1>
                <p class="text-sm text-muted mt-2">{{ $products->total() }} pieces — hand-crafted, hallmarked 22k gold.</p>
            </div>

            <div class="grid lg:grid-cols-[240px_1fr] gap-8">

                {{-- Filters --}}
                <aside class="hidden lg:block">
                    <div class="sticky top-24 space-y-6">

                        {{-- Categories --}}
                        <div>
                            <h3 class="font-serif text-sm uppercase tracking-widest text-charcoal-soft mb-3">Categories</h3>
                            <ul class="space-y-1.5 text-sm">
                                @foreach ($categories as $category)
                                    <li>
                                        <a href="{{ route('categories.show', $category) }}"
                                           class="block text-charcoal-soft hover:text-champagne transition-colors @if (request('category') === $category->slug) text-champagne-dark font-medium @endif">
                                            {{ $category->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Price range --}}
                        <div>
                            <h3 class="font-serif text-sm uppercase tracking-widest text-charcoal-soft mb-3">Price</h3>
                            <form method="get" action="{{ route('shop.index') }}" class="space-y-2">
                                <div class="flex gap-2">
                                    <input type="number" name="min" placeholder="Min" value="{{ request('min') }}" class="text-xs">
                                    <input type="number" name="max" placeholder="Max" value="{{ request('max') }}" class="text-xs">
                                </div>
                                <button type="submit" class="btn-outline text-xs w-full py-2">Apply</button>
                            </form>
                        </div>

                        {{-- Collections --}}
                        @if ($collections->isNotEmpty())
                            <div>
                                <h3 class="font-serif text-sm uppercase tracking-widest text-charcoal-soft mb-3">Collections</h3>
                                <ul class="space-y-1.5 text-sm">
                                    @foreach ($collections as $collection)
                                        <li>
                                            <a href="{{ route('collections.show', $collection) }}" class="text-charcoal-soft hover:text-champagne transition-colors">
                                                {{ $collection->name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Budget shortcuts --}}
                        <div>
                            <h3 class="font-serif text-sm uppercase tracking-widest text-charcoal-soft mb-3">Shop by Budget</h3>
                            <ul class="space-y-1.5 text-sm">
                                <li><a href="{{ route('shop.budget', 'under-15k') }}" class="text-charcoal-soft hover:text-champagne transition-colors">Under Rs. 15k</a></li>
                                <li><a href="{{ route('shop.budget', '15k-to-50k') }}" class="text-charcoal-soft hover:text-champagne transition-colors">Rs. 15k – 50k</a></li>
                                <li><a href="{{ route('shop.budget', '50k-to-150k') }}" class="text-charcoal-soft hover:text-champagne transition-colors">Rs. 50k – 150k</a></li>
                                <li><a href="{{ route('shop.budget', 'above-150k') }}" class="text-charcoal-soft hover:text-champagne transition-colors">Above Rs. 150k</a></li>
                            </ul>
                        </div>
                    </div>
                </aside>

                {{-- Products grid --}}
                <div>
                    {{-- Toolbar --}}
                    <div class="flex items-center justify-between mb-6">
                        <form method="get" action="{{ route('shop.index') }}" class="flex items-center gap-2">
                            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search within results" class="text-sm w-48 md:w-64">
                            <button type="submit" class="btn-outline text-sm py-2">Search</button>
                        </form>
                        <form method="get" action="{{ request()->fullUrlWithoutQuery('page') }}">
                            <select name="sort" class="text-sm" onchange="this.form.submit()">
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
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                            @foreach ($products as $product)
                                @include('storefront.product.card', ['product' => $product])
                            @endforeach
                        </div>

                        <div class="mt-12">
                            {{ $products->links() }}
                        </div>
                    @else
                        <div class="text-center py-20">
                            <p class="font-serif text-2xl text-muted">No pieces found</p>
                            <p class="text-sm text-muted mt-2">Try adjusting your filters or explore our bridal edit.</p>
                            <a href="{{ route('shop.bridal') }}" class="btn-outline mt-6">Bridal Edit</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
