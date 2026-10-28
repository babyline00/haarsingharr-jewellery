@extends('layouts.storefront')

@section('title', ($category->seo_title ?? $category->name).' — Haarsingharr')

@section('content')
    <section class="section">
        <div class="container-haars">

            {{-- Breadcrumb --}}
            @section('breadcrumbs')
            <nav class="text-xs text-muted flex items-center gap-2 flex-wrap" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-champagne transition-colors">Home</a>
                @foreach ($breadcrumb ?? [] as $crumb)
                    <span>/</span>
                    @if ($loop->last)
                        <span class="text-charcoal">{{ $crumb->name }}</span>
                    @else
                        <a href="{{ route('categories.show', $crumb) }}" class="hover:text-champagne transition-colors">{{ $crumb->name }}</a>
                    @endif
                @endforeach
            </nav>
            @endsection

            {{-- Header --}}
            <div class="mb-8 md:mb-10" x-reveal>
                <h1 class="font-serif text-3xl md:text-4xl lg:text-5xl">{{ $category->name }}</h1>
                @if ($category->short_description)
                    <p class="text-base text-muted mt-3 max-w-2xl">{{ $category->short_description }}</p>
                @elseif ($category->description)
                    <p class="text-base text-muted mt-3 max-w-2xl">{{ $category->description }}</p>
                @endif
                <p class="text-sm text-muted mt-2">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('piece', $products->total()) }}</p>
            </div>

            {{-- Subcategories --}}
            @if ($subcategories->isNotEmpty())
                <div class="flex flex-wrap gap-2 mb-8">
                    @foreach ($subcategories as $sub)
                        <a href="{{ route('categories.show', $sub) }}"
                           class="badge bg-ivory-alt text-charcoal-soft hover:text-champagne-dark hover:bg-charcoal/5 transition-colors px-3 py-1.5">{{ $sub->name }}</a>
                    @endforeach
                </div>
            @endif

            {{-- Products --}}
            @if ($products->isNotEmpty())
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6 xl:gap-8" x-reveal="80">
                    @foreach ($products as $product)
                        @include('storefront.product.card', ['product' => $product])
                    @endforeach
                </div>
                <div class="mt-10">{{ $products->links() }}</div>
            @else
                <div class="text-center py-16 md:py-20">
                    <svg class="w-16 h-16 mx-auto text-muted/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <p class="font-serif text-2xl md:text-3xl text-muted mb-3">No pieces found in this category yet</p>
                    <p class="text-sm text-muted mb-6 max-w-xs mx-auto">We're constantly adding new pieces. Check back soon or explore other categories.</p>
                    <a href="{{ route('shop.index') }}" class="btn-gold">Browse All Jewellery</a>
                </div>
            @endif
        </div>
    </section>
@endsection
