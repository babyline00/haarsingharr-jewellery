@php
    // Shared storefront menu renderer. $items = [label, url, children], $variant = desktop|mobile|footer
    $links = collect($items)
        ->filter(fn ($item) => ! empty($item['url']) && $item['url'] !== '#')
        ->map(fn ($item) => [
            'label'    => $item['label'],
            'url'      => $item['url'],
            'children' => collect($item['children'] ?? [])
                ->filter(fn ($child) => ! empty($child['url']) && $child['url'] !== '#')
                ->all(),
        ])
        ->values();
@endphp

@if ($variant === 'desktop')
    @foreach ($links as $i => $link)
        @if (count($link['children']))
            <div class="relative" @mouseenter="open = 'menu-{{ $i }}'" @mouseleave="open = null"
                 @focusin="open = 'menu-{{ $i }}'" @focusout="if (! $el.contains($event.relatedTarget)) open = null">
                <a href="{{ $link['url'] }}"
                   class="relative overflow-hidden group hover:text-champagne transition-colors duration-200 whitespace-nowrap"
                   aria-haspopup="true" :aria-expanded="(open === 'menu-{{ $i }}').toString()">
                    <span class="inline-block transition-transform duration-200 group-hover:translate-x-1">{{ $link['label'] }}</span>
                </a>
                <div x-show="open === 'menu-{{ $i }}'" x-cloak
                     x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                     class="absolute top-full left-1/2 -translate-x-1/2 pt-2 w-[320px]">
                    <div class="bg-porcelain rounded-2xl shadow-xl border border-charcoal/5 p-4">
                        @foreach ($link['children'] as $child)
                            <a href="{{ $child['url'] }}" class="block p-3 rounded-lg hover:bg-ivory-alt transition-colors duration-200">{{ $child['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <a href="{{ $link['url'] }}" class="relative overflow-hidden group hover:text-champagne transition-colors duration-200 whitespace-nowrap">
                <span class="inline-block transition-transform duration-200 group-hover:translate-x-1">{{ $link['label'] }}</span>
            </a>
        @endif
    @endforeach
@elseif ($variant === 'mobile')
    @foreach ($links as $link)
        @if (count($link['children']))
            <div x-data="{ subOpen: false }" class="border-b border-charcoal/5">
                <button type="button" @click="subOpen = !subOpen" aria-expanded="false" :aria-expanded="subOpen.toString()"
                        class="w-full flex items-center justify-between py-3 text-left hover:text-champagne transition-colors">
                    <span>{{ $link['label'] }}</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" :class="{ 'rotate-180': subOpen }"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div x-show="subOpen" x-cloak class="pl-4 pb-2">
                    <a href="{{ $link['url'] }}" class="block py-1.5 text-sm font-medium text-rose-deep hover:text-rose-deep/80">{{ $link['label'] }}</a>
                    @foreach ($link['children'] as $child)
                        <a href="{{ $child['url'] }}" class="block py-2 text-sm text-charcoal-soft hover:text-charcoal">{{ $child['label'] }}</a>
                    @endforeach
                </div>
            </div>
        @else
            <a href="{{ $link['url'] }}" class="flex items-center py-3 border-b border-charcoal/5 transition-all duration-200 hover:translate-x-2 hover:text-champagne">
                <span>{{ $link['label'] }}</span>
            </a>
        @endif
    @endforeach
@else {{-- footer --}}
    @foreach ($links as $link)
        <li>
            <a href="{{ $link['url'] }}" class="text-sm text-ivory/70 hover:text-champagne transition-colors">
                {{ $link['label'] }}
            </a>
            @if (count($link['children']))
                <ul class="mt-2 space-y-2" role="list" aria-label="{{ $link['label'] }} submenu">
                    @foreach ($link['children'] as $child)
                        <li><a href="{{ $child['url'] }}" class="text-sm text-ivory/60 hover:text-champagne transition-colors">{{ $child['label'] }}</a></li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
@endif
