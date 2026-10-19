@extends('layouts.storefront')

@section('title', 'Server Error — Haarsingharr')

@section('content')
    <section class="py-20 md:py-32">
        <div class="container-haars text-center max-w-lg">
            <div class="font-serif text-7xl md:text-9xl text-champagne mb-4">500</div>
            <h1 class="font-serif text-2xl md:text-3xl mb-3">Something unexpected happened.</h1>
            <p class="text-sm text-muted mb-8">
                Our team has been notified. Please try again in a moment, or contact us directly.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('home') }}" class="btn-gold">Return Home</a>
                <a href="{{ route('contact') }}" class="btn-outline">Contact Support</a>
            </div>
        </div>
    </section>
@endsection
