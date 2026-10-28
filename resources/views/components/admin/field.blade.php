@props([
    'name',
    'label' => null,
    'hint' => null,
    'required' => false,
    'id' => null,
])

@php
    $id = $id ?? 'field-' . preg_replace('/[^a-zA-Z0-9_-]+/', '-', $name);
    $oldKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($name) || $errors->has($oldKey);
@endphp

<div {{ $attributes }}>
    @if ($label)
        <label for="{{ $id }}">
            {{ $label }}
            @if ($required)
                <span class="text-danger" aria-hidden="true">*</span>
                <span class="sr-only">(required)</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-xs text-muted mt-1">{{ $hint }}</p>
    @endif

    @if ($hasError)
        <p id="{{ $id }}-error" class="text-xs text-danger mt-1">
            {{ $errors->first($name) ?: $errors->first($oldKey) }}
        </p>
    @endif
</div>
