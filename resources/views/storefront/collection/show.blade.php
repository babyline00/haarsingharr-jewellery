@extends('layouts.storefront')

@section('title', $collection->name.' — Haarsingharr')

@section('content')
    <section class="section">
        <div class="container-haars">

            {{-- Header --}}
            <div class="mb-8 md:mb-10 max-w-3xl" x-reveal>
                <p class="text-champagne-deep text-xs font-semibold tracking-[0.3em] uppercase mb-2">Collection</p>
                <h1 class="font-serif text-3xl md:text-4xl lg:text-5xl">{{ $collection->name }}</h1>
                @if ($collection->description)
                    <p class="text-base text-muted mt-3">{{ $collection->description }}</p>
                @endif
                <p class="text-sm text-muted mt-2">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('piece', $products->total()) }}</p>
            </div>

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
                    <svg class="w-16 h-16 mx-auto text-muted/40 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8-4v10l-8 4m0-10L4 7m8-4v10l-8 4m0-10L4 7m8-4v10"/></svg>
                    <p class="font-serif text-2xl md:text-3xl text-muted mb-3">No pieces in this collection yet</p>
                    <p class="text-sm text-muted mb-6 max-w-xs mx-auto">We're curating new pieces for this collection. Check back soon.</p>
                    <a href="{{ route('shop.index') }}" class="btn-gold mt-4">Browse All Jewellery</a>
                </div>
            @endif
        </div>
    </section>
@endsection