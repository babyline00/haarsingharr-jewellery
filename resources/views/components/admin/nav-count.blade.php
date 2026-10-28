@props([
    'count' => 0,
    'label' => 'pending',
])

@if ($count > 0)
    <span class="ml-auto badge badge-champagne badge-sm" aria-label="{{ $count }} {{ $label }}">
        {{ $count > 99 ? '99+' : $count }}
    </span>
@endif
