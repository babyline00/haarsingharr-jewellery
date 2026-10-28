@extends('layouts.storefront')

@section('title', $tag ? ($tag->name.' — Blog') : 'Blog — Haarsingharr.pk')

@section('content')
    <section class="bg-porcelain py-12 md:py-16">
        <div class="container-haars">
            <p class="text-champagne text-xs tracking-[0.3em] uppercase mb-3">Haarsingharr Journal</p>
            <h1 class="font-serif text-4xl md:text-5xl text-balance">{{ $tag ? $tag->name : 'Stories & Styling' }}</h1>
            <p class="text-muted mt-3 max-w-xl">Care guides, styling notes and the stories behind the pieces.</p>
        </div>
    </section>

    <section class="py-10 md:py-16">
        <div class="container-haars grid lg:grid-cols-[1fr_260px] gap-8">
            <div>
                @if ($posts->isEmpty())
                    <div class="text-center py-16">
                        <p class="font-serif text-xl mb-2">Nothing published yet</p>
                        <p class="text-sm text-muted">Journal entries will appear here once published.</p>
                    </div>
                @else
                    <div class="grid sm:grid-cols-2 gap-6">
                        @foreach ($posts as $post)
                            <article class="group">
                                @if ($post->featured_image)
                                    <a href="{{ route('blog.show', $post) }}" class="block overflow-hidden rounded-xl mb-4">
                                        <img src="{{ \App\Support\Media::url($post->featured_image, \App\Support\Media::WIDE_PLACEHOLDER) }}"
                                             alt="{{ $post->title }}" loading="lazy"
                                             class="w-full h-48 object-cover transition-transform duration-700 group-hover:scale-105">
                                    </a>
                                @endif
                                <p class="text-xs text-muted mb-1">
                                    {{ $post->published_at?->format('M j, Y') }}
                                    @if ($post->tags->isNotEmpty())
                                        · {{ $post->tags->pluck('name')->implode(', ') }}
                                    @endif
                                </p>
                                <h2 class="font-serif text-xl leading-snug mb-2">
                                    <a href="{{ route('blog.show', $post) }}" class="hover:text-champagne-dark transition-colors">{{ $post->title }}</a>
                                </h2>
                                <p class="text-sm text-charcoal-soft">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->content), 120) }}</p>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-8">{{ $posts->withQueryString()->links() }}</div>
                @endif
            </div>

            <aside>
                <h2 class="font-serif text-lg mb-3">Browse by tag</h2>
                <nav class="space-y-1" aria-label="Blog tags">
                    <a href="{{ route('blog.index') }}"
                       class="block text-sm px-3 py-2 rounded-md transition-colors {{ ! $tag ? 'bg-champagne/20 text-champagne-dark' : 'hover:bg-ivory-alt' }}">
                        All posts
                    </a>
                    @foreach ($tags as $postTag)
                        <a href="{{ route('blog.index', ['tag' => $postTag->slug]) }}"
                           class="block text-sm px-3 py-2 rounded-md transition-colors {{ $tag?->id === $postTag->id ? 'bg-champagne/20 text-champagne-dark' : 'hover:bg-ivory-alt' }}">
                            {{ $postTag->name }} <span class="text-xs text-muted">({{ $postTag->posts_count }})</span>
                        </a>
                    @endforeach
                </nav>
            </aside>
        </div>
    </section>
@endsection
