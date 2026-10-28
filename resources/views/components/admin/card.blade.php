@props([
    'title' => null,
    'subtitle' => null,
    'flush' => false,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <div class="px-5 py-4 border-b border-charcoal/5 flex items-start justify-between gap-3">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="font-serif text-lg leading-tight">{{ $title }}</h2>
                @endif
                @if ($subtitle)
                    <p class="text-xs text-muted mt-0.5">{{ $subtitle }}</p>
                @endif
            </div>

            @if (isset($actions))
                <div class="flex items-center gap-2 flex-shrink-0">{{ $actions }}</div>
            @endif
        </div>
    @endif

    <div class="{{ $flush ? '' : ($padding ? 'p-5' : '') }}">
        {{ $slot }}
    </div>

    @if (isset($footer))
        <div class="px-5 py-3 border-t border-charcoal/5 bg-ivory-alt/20">
            {{ $footer }}
        </div>
    @endif
</div>
