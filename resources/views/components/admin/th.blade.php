@props([
    'label' => null,
    'field' => null,
    'align' => 'left',
])

@php
    $sort = request('sort');
    $dir = request('dir', 'asc');
    $isActive = $field && $sort === $field;
    $nextDir = $isActive && $dir === 'asc' ? 'desc' : 'asc';
    $url = $field ? request()->fullUrlWithQuery(['sort' => $field, 'dir' => $nextDir]) : null;

    $alignClass = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
@endphp

<th scope="col" class="{{ $alignClass }} p-3 font-medium"
    @if ($isActive) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif>
    @if ($url)
        <a href="{{ $url }}" class="inline-flex items-center gap-1 hover:text-charcoal transition-colors">
            {{ $label ?? $slot }}
            @if ($isActive)
                <x-admin.icon :name="$dir === 'asc' ? 'chevron-up' : 'chevron-down'" :size="12" class="text-champagne-dark" />
            @else
                <x-admin.icon name="chevron-down" :size="12" class="opacity-30" />
            @endif
        </a>
    @else
        {{ $label ?? $slot }}
    @endif
</th>
