{{--
    Command palette (⌘K) — permission-aware instant results (orders /
    customers / products), quick actions and recent searches.

    Keyboard: ⌘K/Ctrl-K or trigger click opens, Esc closes, ↑/↓ move,
    Enter visits the selection (or full results page when nothing matches).
    Recent searches come from localStorage. Focus is trapped while open and
    restored to the trigger on close.
--}}
<div x-data="palette({{ \Illuminate\Support\Js::from([
        'suggestUrl' => route('admin.search.suggest'),
        'exactUrl'   => route('admin.search'),
    ])->toHtml() }})"
     @keydown.window.meta.k.prevent="toggle()"
     @keydown.window.ctrl.k.prevent="toggle()"
     @keydown.window.escape="if (open) close()">

    {{-- Trigger (desktop) — pure link fallback to full search without JS --}}
    <a href="{{ route('admin.search') }}"
       x-on:click.prevent="open ? close() : toggle()"
       class="hidden lg:flex items-center gap-2 w-64 rounded-md px-3 py-2 text-left text-sm bg-charcoal/30 border border-charcoal/20 text-ivory/60 hover:bg-charcoal/40 hover:border-champagne/50 transition-all duration-200"
       role="button" aria-haspopup="dialog"
       :aria-expanded="open ? 'true' : 'false'"
       aria-label="Search or jump to (command palette)"
       x-ref="trigger">
        <x-admin.icon name="search" :size="16" class="text-champagne" />
        <span class="flex-1 truncate">Search or jump to…</span>
        <span class="px-1.5 py-0.5 rounded border border-charcoal/20 text-[10px] text-ivory/50 select-none">⌘K</span>
    </a>

    {{-- Mobile fallback: plain input submitting to the results page --}}
    <form action="{{ route('admin.search') }}" method="get" role="search" class="lg:hidden">
        <label for="global-search-sm" class="sr-only">Search admin</label>
        <input type="search" id="global-search-sm" name="q"
               placeholder="Search…" class="w-36 sm:w-48 bg-charcoal/30 border border-charcoal/20 text-ivory placeholder-charcoal/40 rounded-md px-3 py-2 text-sm">
    </form>

    {{-- Overlay --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-[var(--z-modal)] flex items-start justify-center pt-[10vh] px-4 sm:px-4"
         @keydown.arrow-down.prevent="move(1)"
         @keydown.arrow-up.prevent="move(-1)"
         @keydown.enter.prevent="visit()"
         @keydown.tab="trap($event)"
         role="dialog" aria-modal="true" aria-label="Command palette"
         @click.self="close()">
        <div class="fixed inset-0 bg-charcoal/60" aria-hidden="true"></div>

        <div class="relative w-full max-w-xl max-[400px]:max-w-full bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="flex items-center gap-2 border-b border-charcoal/10 px-4">
                <x-admin.icon name="search" :size="18" class="text-champagne-dark flex-shrink-0" />
                <label for="palette-input" class="sr-only">Search orders, customers, products — or jump to a page</label>
                <input type="search" id="palette-input" x-model="q" x-ref="paletteInput"
                       @input.debounce.300ms="fetchResults()" class="w-full py-4 text-base bg-transparent focus:outline-none"
                       placeholder="Search orders, customers, products — or jump to a page…">
            </div>

            <div class="max-h-[55vh] overflow-y-auto text-sm">
                {{-- No query yet: recents + quick actions --}}
                <div x-show="q.length < 2">
                    <template x-if="recent.length">
                        <div class="py-2 border-b border-charcoal/5">
                            <p class="px-4 pb-1 text-[10px] uppercase tracking-widest text-muted">Recent searches</p>
                            <ul class="px-1 pb-1">
                                <template x-for="(item, i) in recent" :key="'recent'+i">
                                    <li>
                                        <a :href="item.url" @click="remember(item)"
                                           class="flex items-center justify-between gap-3 px-3 py-2 mx-1 rounded-lg hover:bg-ivory-alt/60 transition-colors">
                                            <span class="truncate font-medium" x-text="item.title"></span>
                                            <span class="text-xs text-muted" x-text="item.meta"></span>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <p class="px-4 pt-3 pb-1 text-[10px] uppercase tracking-widest text-muted">Quick actions</p>
                    <ul class="px-1 pb-2">
                        <template x-for="(action, i) in actions" :key="'action'+i">
                            <li>
                                <a :href="action.url" @click="close()"
                                   class="flex items-center gap-3 px-3 py-2 mx-1 rounded-lg hover:bg-ivory-alt/60 transition-colors">
                                    <span class="w-7 h-7 rounded-md bg-champagne-pale text-champagne-deep flex items-center justify-center flex-shrink-0">
                                        <x-admin.icon name="arrow-right" :size="14" />
                                    </span>
                                    <span class="truncate font-medium" x-text="action.title"></span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Instant results --}}
                <div x-show="q.length >= 2" x-cloak>
                    <template x-for="group in groups" :key="group.key">
                        <div class="py-2">
                            <p class="px-4 pb-1 text-[10px] uppercase tracking-widest text-muted" x-text="group.label"></p>
                            <ul class="px-1" :aria-label="group.label">
                                <template x-for="(item, i) in group.items" :key="group.key+i">
                                    <li>
                                        <a :href="item.url"
                                           @click="remember(item)" @focusin="setFlat(group.key, i)"
                                           :class="isCurrent(group.key, i) ? 'bg-champagne/15' : ''"
                                           class="flex items-center justify-between gap-3 px-3 py-2 mx-1 rounded-lg hover:bg-ivory-alt/60 transition-colors">
                                            <span class="truncate font-medium" x-text="item.title"></span>
                                            <span class="flex-shrink-0 text-xs text-muted truncate max-w-40" x-text="item.meta"></span>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>

                    <p x-show="flat.length === 0" class="px-4 py-6 text-center text-muted">
                        No matches for “<span x-text="q"></span>” — press Enter for full search.
                    </p>

                    <div class="px-4 pb-3 pt-1 mt-1 text-[10px] text-muted flex justify-between border-t border-charcoal/5">
                        <span><kbd class="px-1 border rounded border-charcoal/10">↑↓</kbd> move · <kbd class="px-1 border rounded border-charcoal/10">Enter</kbd> open</span>
                        <span><kbd class="px-1 border rounded border-charcoal/10">Esc</kbd> close</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
