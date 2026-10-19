@extends('layouts.storefront')

@section('title', 'Verifying Payment — Haarsingharr')

@section('content')
    <section class="py-20">
        <div class="container-haars max-w-md text-center">
            <div class="w-16 h-16 mx-auto rounded-full border-2 border-champagne border-t-transparent animate-spin mb-6"></div>
            <h1 class="font-serif text-2xl mb-3">Verifying your payment…</h1>
            <p class="text-sm text-muted">Please do not close this window. We're confirming with the payment provider.</p>
            @if (isset($next_url))
                <p class="text-xs text-muted mt-4">You'll be redirected to <a href="{{ $next_url }}" class="text-champagne-dark hover:underline">your order</a> once verification completes.</p>
            @endif
        </div>
    </section>
@endsection
