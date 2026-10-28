@props([
    'id',
    'label',
])

<div x-data="{ open: $persist(true).as('admin-nav-{{ $id }}') }">
    <button type="button" x-on:click="open = !open"
            class="sidebar-section-title w-full flex items-center justify-between gap-2 hover:text-charcoal-soft transition-colors"
            :aria-expanded="open.toString()">
        <span>{{ $label }}</span>
        <x-admin.icon name="chevron-down" :size="12"
                      class="transition-transform duration-200"
                      x-bind:class="open ? '' : '-rotate-90'" />
    </button>

    <div x-show="open" x-collapse>
        {{ $slot }}
    </div>
</div>
