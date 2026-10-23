@extends('layouts.storefront')

@section('title', 'Our Boutiques — Haarsingharr')

@section('content')
    <section class="py-12 md:py-16">
        <div class="container-haars max-w-5xl">
            <div class="text-center mb-8 md:mb-12">
                <h1 class="font-serif text-3xl md:text-4xl lg:text-5xl mb-3">Visit Haarsingharr</h1>
                <p class="text-base md:text-lg text-muted max-w-2xl mx-auto">Experience our pieces in person. Our knowledgeable staff are ready to help you find the perfect piece.</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 xl:gap-8">
                @foreach ($locations as $location)
                    <article class="card p-6 card-hover">
                        @if ($location->is_main)
                            <span class="badge bg-champagne text-charcoal mb-3">Flagship</span>
                        @endif
                        <h2 class="font-serif text-xl mb-2">{{ $location->name }}</h2>
                        <p class="text-sm text-muted mb-3">{{ $location->address }}</p>

                        <div class="space-y-2 text-sm">
                            @if ($location->phone)
                                <a href="tel:{{ $location->phone }}" class="flex items-center gap-2 text-charcoal-soft hover:text-champagne transition-colors">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <span>{{ $location->phone }}</span>
                                </a>
                            @endif
                            @if ($location->email)
                                <a href="mailto:{{ $location->email }}" class="flex items-center gap-2 text-charcoal-soft hover:text-champagne transition-colors">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M22 6l-10 7L2 6"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
                                    <span>{{ $location->email }}</span>
                                </a>
                            @endif
                            @if ($location->whatsapp)
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $location->whatsapp) }}" target="_blank" class="flex items-center gap-2 text-success font-medium">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M21 11.5a8.4 8.4 0 0 1-12 7.6L4 22l1.9-5A8.4 8.4 0 1 1 21 11.5z"/></svg>
                                    Chat on WhatsApp
                                </a>
                            @endif
                        </div>

                        @if ($location->hours)
                            <div class="text-xs text-muted mt-4 pt-3 border-t border-charcoal/5">
                                <div class="font-medium text-charcoal-soft mb-1">Hours</div>
                                <div class="whitespace-pre-line">{{ $location->hours }}</div>
                            </div>
                        @endif

                        @if ($location->map_link)
                            <a href="{{ $location->map_link }}" target="_blank" class="text-xs text-champagne-dark hover:underline mt-3 inline-block flex items-center gap-1">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                View on map
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="mt-10 md:mt-12 text-center">
                <p class="text-sm text-muted mb-4">Prefer to shop from home?</p>
                <a href="{{ route('shop.index') }}" class="btn-gold">Browse the Collection</a>
            </div>
        </div>
    </section>
@endsection