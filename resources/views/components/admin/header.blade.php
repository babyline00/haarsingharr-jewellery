@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'mb-6']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-serif text-2xl md:text-3xl tracking-tight">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-sm text-muted mt-1 max-w-2xl">{{ $subtitle }}</p>
            @endif
        </div>

        @if (isset($actions))
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>

    @if (isset($meta))
        <div class="mt-3">{{ $meta }}</div>
    @endif
</div>
