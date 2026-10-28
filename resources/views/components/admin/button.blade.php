@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconPosition' => 'left',
])

@php
    $variants = [
        'primary' => 'btn-gold',
        'gold' => 'btn-gold',
        'outline' => 'btn-outline',
        'ghost' => 'btn-ghost',
        'danger' => 'btn-danger',
        'secondary' => 'btn-secondary',
        'charcoal' => 'btn-primary',
    ];

    $sizes = ['sm' => 'btn-sm', 'md' => '', 'lg' => 'btn-lg'];

    $classes = trim(($variants[$variant] ?? 'btn-gold') . ' ' . ($sizes[$size] ?? ''));
@endphp

@if ($href)
    @php
        // External links opening a new tab must not gain window.opener access.
        if ($attributes->get('target') === '_blank' && ! $attributes->get('rel')) {
            $attributes = $attributes->merge(['rel' => 'noopener']);
        }
    @endphp
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <x-admin.icon :name="$icon" :size="16" />
        @endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')
            <x-admin.icon :name="$icon" :size="16" />
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <x-admin.icon :name="$icon" :size="16" />
        @endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')
            <x-admin.icon :name="$icon" :size="16" />
        @endif
    </button>
@endif
