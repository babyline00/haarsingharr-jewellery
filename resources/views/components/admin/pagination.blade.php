@props([
    'paginator' => null,
])

@if ($paginator)
    <div {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3 px-1 py-2']) }}>
        <p class="text-xs text-muted">
            @if ($paginator->total() > 0)
                Showing
                <span class="font-medium text-charcoal-soft">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                of
                <span class="font-medium text-charcoal-soft">{{ number_format($paginator->total()) }}</span>
            @else
                No results
            @endif
        </p>

        @if ($paginator->hasPages())
            <div>{{ $paginator->links() }}</div>
        @endif
    </div>
@endif
