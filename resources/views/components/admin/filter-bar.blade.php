@props([
    'action' => null,
    'method' => 'GET',
])

<form method="{{ $method }}" @if ($action) action="{{ $action }}" @endif
      {{ $attributes->merge(['class' => 'card p-4 mb-5']) }} role="search">
    <div class="flex flex-wrap items-end gap-3">
        {{ $slot }}
    </div>
</form>
