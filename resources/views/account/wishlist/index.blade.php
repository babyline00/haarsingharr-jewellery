@extends('layouts.storefront')

@section('title', 'My Wishlist — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-5xl">
            <h1 class="font-serif text-3xl md:text-4xl mb-8">Saved Items</h1>

            @if ($wishlist && $wishlist->items->isNotEmpty())
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                    @foreach ($wishlist->items as $item)
                        @if ($product = $item->product)
                            @include('storefront.product.card', ['product' => $product])
                        @endif
                    @endforeach
                </div>
            @else
                <div class="text-center py-16">
                    <p class="font-serif text-xl text-muted">Your wishlist is empty</p>
                    <p class="text-sm text-muted mt-2">Save pieces you love and revisit them later.</p>
                    <a href="{{ route('shop.index') }}" class="btn-gold mt-4">Explore the Collection</a>
                </div>
            @endif
        </div>
    </section>
@endsection
