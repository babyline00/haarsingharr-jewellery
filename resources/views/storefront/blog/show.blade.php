@extends('layouts.storefront')

@section('title', $post->seo_title ?: $post->title)

@section('meta_description', $post->seo_description ?: \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->content), 150))

@section('content')
    @if (! empty($isPreview))
        <div class="bg-charcoal text-champagne text-center text-xs py-2 px-4" role="status">
            Preview — this post is {{ $post->status }} and may not be published yet.
        </div>
    @endif

    <article>
        <header class="bg-porcelain py-10 md:py-14">
            <div class="container-haars max-w-3xl">
                <p class="text-champagne text-xs tracking-[0.25em] uppercase mb-3">Journal</p>
                <h1 class="font-serif text-3xl md:text-5xl leading-tight text-balance">{{ $post->title }}</h1>
                <p class="text-sm text-muted mt-3">
                    {{ $post->published_at?->format('F j, Y') ?? 'Unpublished' }}
                    @if ($post->author) · {{ $post->author->name }} @endif
                    @if ($post->tags->isNotEmpty())
                        <span class="ml-1">·</span>
                        @foreach ($post->tags as $tag)
                            <a href="{{ route('blog.index', ['tag' => $tag->slug]) }}" class="text-champagne-dark hover:underline">{{ $tag->name }}</a>@if (! $loop->last), @endif
                        @endforeach
                    @endif
                </p>
            </div>
        </header>

        @if ($post->featured_image)
            <div class="container-haars -mt-6">
                <img src="{{ \App\Support\Media::url($post->featured_image, \App\Support\Media::WIDE_PLACEHOLDER) }}"
                     alt="{{ $post->title }}" loading="eager"
                     class="w-full max-h-[480px] object-cover rounded-2xl border border-charcoal/5">
            </div>
        @endif

        <div class="container-haars max-w-3xl py-10 md:py-14">
            <div class="prose-haars font-serif leading-relaxed text-charcoal-soft">
                @if ($post->excerpt)
                    <p class="text-base md:text-lg text-charcoal mb-6">{{ $post->excerpt }}</p>
                @endif

                {!! \Illuminate\Support\Str::markdown((string) $post->content) !!}
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="container-haars py-10 border-t border-charcoal/5">
                <h2 class="font-serif text-2xl mb-6">Continue reading</h2>
                <div class="grid sm:grid-cols-3 gap-6">
                    @foreach ($related as $relatedPost)
                        <article class="group">
                            @if ($relatedPost->featured_image)
                                <a href="{{ route('blog.show', $relatedPost) }}" class="block overflow-hidden rounded-xl mb-3">
                                    <img src="{{ \App\Support\Media::url($relatedPost->featured_image, \App\Support\Media::WIDE_PLACEHOLDER) }}"
                                         alt="{{ $relatedPost->title }}" loading="lazy"
                                         class="w-full h-40 object-cover transition-transform duration-700 group-hover:scale-105">
                                </a>
                            @endif
                            <h3 class="font-serif text-lg leading-snug">
                                <a href="{{ route('blog.show', $relatedPost) }}" class="hover:text-champagne-dark transition-colors">{{ $relatedPost->title }}</a>
                            </h3>
                            <p class="text-xs text-muted mt-1">{{ $relatedPost->published_at?->format('M j, Y') }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection
