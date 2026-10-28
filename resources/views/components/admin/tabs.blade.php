@props([
    'items' => [],
])

@if (count($items))
    <nav aria-label="Sections" {{ $attributes->merge(['class' => 'tabs mb-6 overflow-x-auto']) }}>
        @foreach ($items as $item)
            <a href="{{ $item['url'] }}"
               class="tab whitespace-nowrap {{ ! empty($item['active']) ? 'active' : '' }}"
               @if (! empty($item['active'])) aria-current="page" @endif>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>
@endif
