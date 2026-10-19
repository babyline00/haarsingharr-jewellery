@extends('layouts.storefront')

@section('title', $page->seo_title ?? $page->title . ' — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-3xl">
            <h1 class="font-serif text-3xl md:text-4xl mb-6">{{ $page->title }}</h1>

            @if ($page->excerpt)
                <p class="text-lg text-muted mb-6">{{ $page->excerpt }}</p>
            @endif

            @if ($page->featured_image)
                <img src="{{ $page->featured_image }}" alt="{{ $page->title }}" class="w-full rounded-2xl mb-8">
            @endif

            <div class="prose prose-sm max-w-none text-charcoal-soft leading-relaxed">
                {!! $page->content !!}
            </div>

            @if ($page->sections->isNotEmpty())
                @foreach ($page->sections as $section)
                    <section class="mt-8 pt-8 border-t border-charcoal/5">
                        @if ($section->title)
                            <h2 class="font-serif text-xl mb-3">{{ $section->title }}</h2>
                        @endif
                        <div class="text-charcoal-soft text-sm leading-relaxed">{!! $section->content !!}</div>
                    </section>
                @endforeach
            @endif
        </div>
    </section>
@endsection
