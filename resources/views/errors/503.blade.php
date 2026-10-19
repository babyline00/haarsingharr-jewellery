@extends('layouts.storefront')

@section('title', 'Under Maintenance — Haarsingharr')

@section('content')
    <section class="py-20 md:py-32">
        <div class="container-haars text-center max-w-lg">
            <div class="font-serif text-7xl md:text-9xl text-champagne mb-4">503</div>
            <h1 class="font-serif text-2xl md:text-3xl mb-3">We're polishing the showcase.</h1>
            <p class="text-sm text-muted mb-8">
                Haarsingharr is briefly offline for routine maintenance. We'll be back shortly.
            </p>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('contact') }}" class="btn-outline">Contact Support</a>
            </div>
        </div>
    </section>
@endsection
