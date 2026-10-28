<svg {{ $attributes->merge(['class' => 'flex-shrink-0']) }}
     width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24"
     fill="none" stroke="currentColor" stroke-width="{{ $stroke }}"
     stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    {!! $inner !!}
</svg>
