@props([
    'caption' => null,
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto']) }}>
    <table class="table">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        {{ $slot }}
    </table>
</div>
