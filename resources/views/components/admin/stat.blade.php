@props([
    'label',
    'value',
    'delta' => null,
    'trend' => 'flat',
    'icon' => null,
    'href' => null,
])

@php
    $trendClass = match ($trend) {
        'up' => 'text-success',
        'down' => 'text-danger',
        default => 'text-muted',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'card card-hover p-5 block']) }}>
@else
    <div {{ $attributes->merge(['class' => 'card p-5']) }}>
@endif
        <div class="flex items-start justify-between gap-3">
            <p class="text-xs uppercase tracking-widest text-muted">{{ $label }}</p>
            @if ($icon)
                <span class="text-champagne-dark/80" aria-hidden="true">
                    <x-admin.icon :name="$icon" :size="18" />
                </span>
            @endif
        </div>

        <p class="font-serif text-2xl md:text-3xl mt-2 tabular-nums">{{ $value }}</p>

        @if ($delta)
            <p class="text-xs mt-1 {{ $trendClass }}">{{ $delta }}</p>
        @endif

@if ($href)
    </a>
@else
    </div>
@endif
