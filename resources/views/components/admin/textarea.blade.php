@props([
    'name',
    'value' => null,
    'id' => null,
    'rows' => 4,
    'describedby' => null,
])

@php
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name);
    $oldKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($name) || $errors->has($oldKey);
    $current = old($oldKey, $value);
    $describes = trim(($hasError ? $id.'-error ' : '').(string) $describedby);
@endphp

<textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
          @if ($hasError) aria-invalid="true" @endif
          @if ($describes) aria-describedby="{{ $describes }}" @endif
          {{ $attributes }}>{{ $current }}</textarea>
