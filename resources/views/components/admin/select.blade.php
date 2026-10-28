@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'id' => null,
    'describedby' => null,
])

@php
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name);
    $oldKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($name) || $errors->has($oldKey);
    $current = old($oldKey, $selected);
    $describes = trim(($hasError ? $id.'-error ' : '').(string) $describedby);
@endphp

<select id="{{ $id }}" name="{{ $name }}"
        @if ($hasError) aria-invalid="true" @endif
        @if ($describes) aria-describedby="{{ $describes }}" @endif
        {{ $attributes }}>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $label }}</option>
    @endforeach

    {{ $slot }}
</select>
