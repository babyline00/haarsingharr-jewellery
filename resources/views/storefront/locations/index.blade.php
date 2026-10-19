@extends('layouts.storefront')

@section('title', 'Our Boutiques — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-5xl">
            <h1 class="font-serif text-3xl md:text-4xl mb-8">Visit Haarsingharr</h1>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($locations as $location)
                    <div class="card p-6">
                        @if ($location->is_main)
                            <span class="badge bg-champagne text-charcoal mb-2">Flagship</span>
                        @endif
                        <h2 class="font-serif text-xl">{{ $location->name }}</h2>
                        <p class="text-sm text-muted mt-2">{{ $location->address }}</p>

                        @if ($location->phone)
                            <a href="tel:{{ $location->phone }}" class="block text-sm text-charcoal-soft mt-3 hover:text-champagne-dark transition-colors">
                                {{ $location->phone }}
                            </a>
                        @endif
                        @if ($location->email)
                            <a href="mailto:{{ $location->email }}" class="block text-sm text-charcoal-soft hover:text-champagne-dark transition-colors">
                                {{ $location->email }}
                            </a>
                        @endif
                        @if ($location->whatsapp)
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $location->whatsapp) }}" target="_blank" class="block text-sm text-success mt-2">Chat on WhatsApp →</a>
                        @endif

                        @if ($location->hours)
                            <div class="text-xs text-muted mt-3 pt-3 border-t border-charcoal/5">
                                <div class="font-medium text-charcoal-soft mb-1">Hours</div>
                                {{ $location->hours }}
                            </div>
                        @endif

                        @if ($location->map_link)
                            <a href="{{ $location->map_link }}" target="_blank" class="text-xs text-champagne-dark hover:underline mt-3 inline-block">View on map →</a>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-12 text-center">
                <p class="text-sm text-muted mb-4">Prefer to shop from home?</p>
                <a href="{{ route('shop.index') }}" class="btn-gold">Browse the Collection</a>
            </div>
        </div>
    </section>
@endsection
