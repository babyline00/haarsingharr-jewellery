@extends('layouts.storefront')

@section('title', 'FAQ — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-3xl">
            <h1 class="font-serif text-3xl md:text-4xl mb-8">Frequently Asked Questions</h1>

            @foreach ($groupedFaqs as $category => $faqs)
                <div class="mb-10" x-data="{ open: null }">
                    <h2 class="font-serif text-xl mb-4 text-champagne-dark">{{ $category }}</h2>
                    <div class="space-y-3">
                        @foreach ($faqs as $faq)
                            <div class="card overflow-hidden">
                                <button @click="open === {{ $faq->id }} ? open = null : open = {{ $faq->id }}" class="w-full text-left p-4 flex items-center justify-between hover:bg-ivory-alt/30 transition-colors">
                                    <span class="font-medium text-charcoal-soft text-sm">{{ $faq->question }}</span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted flex-shrink-0"
                                         x-show="open !== {{ $faq->id }}"><polyline points="6 9 12 15 18 9"/></svg>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-muted flex-shrink-0"
                                         x-show="open === {{ $faq->id }}" x-cloak><polyline points="18 15 12 9 6 15"/></svg>
                                </button>
                                <div x-show="open === {{ $faq->id }}" x-collapse x-cloak class="px-4 pb-4 text-sm text-muted leading-relaxed">
                                    {{ $faq->answer }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="mt-12 p-6 bg-ivory-alt/30 rounded-lg text-center">
                <h2 class="font-serif text-xl mb-2">Still have questions?</h2>
                <p class="text-sm text-muted mb-4">Our concierge team is here to help.</p>
                <a href="{{ route('contact') }}" class="btn-outline">Contact Us</a>
            </div>
        </div>
    </section>
@endsection
