@extends('layouts.storefront')

@section('title', 'Page Not Found — Haarsingharr')

@section('content')
    <section class="py-20 md:py-32">
        <div class="container-haars text-center max-w-lg">
            <div class="font-serif text-7xl md:text-9xl text-champagne mb-4">404</div>
            <h1 class="font-serif text-2xl md:text-3xl mb-3">This page has slipped away.</h1>
            <p class="text-sm text-muted mb-8">
                The piece you're searching for may have moved to a new setting. Let us help you find
                what you're looking for.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('home') }}" class="btn-gold">Return Home</a>
                <a href="{{ route('shop.index') }}" class="btn-outline">Browse the Collection</a>
            </div>
        </div>
    </section>
@endsection
