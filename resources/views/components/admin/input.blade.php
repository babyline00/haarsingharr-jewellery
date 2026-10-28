@props([
    'name',
    'type' => 'text',
    'value' => null,
    'id' => null,
    'describedby' => null,
])

@php
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name);
    $oldKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($name) || $errors->has($oldKey);
    $current = old($oldKey, $value);
    // Error text + any extra hint text ids, e.g. a help hint from the field wrapper.
    $describes = trim(($hasError ? $id.'-error ' : '').(string) $describedby);
@endphp

<input type="{{ $type }}" id="{{ $id }}" name="{{ $name }}" value="{{ $current }}"
       @if ($hasError) aria-invalid="true" @endif
       @if ($describes) aria-describedby="{{ $describes }}" @endif
       {{ $attributes }}>
