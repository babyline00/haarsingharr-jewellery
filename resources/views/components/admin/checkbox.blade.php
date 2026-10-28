@props([
    'name',
    'value' => '1',
    'checked' => false,
    'label' => null,
    'id' => null,
])

@php
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name);
    $oldKey = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($oldKey, $checked);
@endphp

<label for="{{ $id }}" {{ $attributes->merge(['class' => 'flex items-start gap-2.5 cursor-pointer font-normal mb-0']) }}>
    <input type="checkbox" id="{{ $id }}" name="{{ $name }}" value="{{ $value }}"
           @checked(is_array($current) ? in_array($value, $current) : (bool) $current)
           class="mt-0.5">

    @if ($label)
        <span class="text-sm text-charcoal-soft leading-snug">{{ $label }}</span>
    @else
        {{ $slot }}
    @endif
</label>
