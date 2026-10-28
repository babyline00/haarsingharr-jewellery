@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="pagination flex-wrap">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="opacity-40" aria-disabled="true" aria-label="Previous page">
                <x-admin.icon name="chevron-left" :size="16" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                <x-admin.icon name="chevron-left" :size="16" />
            </a>
        @endif

        {{-- Page links --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="ellipsis" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="current" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                <x-admin.icon name="chevron-right" :size="16" />
            </a>
        @else
            <span class="opacity-40" aria-disabled="true" aria-label="Next page">
                <x-admin.icon name="chevron-right" :size="16" />
            </span>
        @endif
    </nav>
@endif
