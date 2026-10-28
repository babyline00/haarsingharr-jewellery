@php
    $items = \App\Support\Admin\Breadcrumbs::forCurrentRoute();
@endphp

@if (count($items) > 1)
    <nav aria-label="Breadcrumb" class="mb-4">
        <ol class="flex flex-wrap items-center gap-1.5 text-xs">
            @foreach ($items as $item)
                <li class="flex items-center gap-1.5">
                    @if (! $loop->last && ! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="text-muted hover:text-champagne-dark transition-colors">{{ $item['label'] }}</a>
                        <x-admin.icon name="chevron-right" :size="12" class="text-charcoal/20" />
                    @else
                        <span @if ($loop->last) aria-current="page" @endif
                              class="{{ $loop->last ? 'text-charcoal-soft font-medium' : 'text-muted' }}">{{ $item['label'] }}</span>
                        @unless ($loop->last)
                            <x-admin.icon name="chevron-right" :size="12" class="text-charcoal/20" />
                        @endunless
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
