@props([
    'name',
    'title',
    'maxWidth' => 'max-w-lg',
])

{{--
    Usage:
      <x-admin.modal name="delete-user" title="Delete user">
          ...content...
          <x-slot:footer>...</x-slot:footer>
      </x-admin.modal>

      <button x-on:click="$dispatch('open-modal-delete-user')">Open</button>
--}}

<div x-data="{
         open: false,
         trigger: null,
         bodyLocked: false,
         syncBodyLock() {
             if (this.open && !this.bodyLocked) {
                 window.__adminDialogCount = (window.__adminDialogCount || 0) + 1;
                 this.bodyLocked = true;
             } else if (!this.open && this.bodyLocked) {
                 window.__adminDialogCount = Math.max(0, (window.__adminDialogCount || 0) - 1);
                 this.bodyLocked = false;
             }
             document.body.classList.toggle('overflow-hidden', (window.__adminDialogCount || 0) > 0);
         },
         trap(event) {
             const items = [...this.$refs.panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]')].filter((item) => item.offsetParent !== null && item.tabIndex !== -1);
             if (!items.length) { event.preventDefault(); this.$refs.panel.focus(); return; }
             const first = items[0], last = items[items.length - 1];
             if (!items.includes(document.activeElement) && document.activeElement !== this.$refs.panel) { event.preventDefault(); (event.shiftKey ? last : first).focus(); return; }
             if (event.shiftKey && (document.activeElement === first || document.activeElement === this.$refs.panel)) { event.preventDefault(); last.focus(); }
             else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
         }
     }"
     x-on:open-modal-{{ $name }}.window="trigger = document.activeElement; open = true; $nextTick(() => $refs.panel?.focus())"
     x-on:close-modal-{{ $name }}.window="open = false; $nextTick(() => trigger?.focus())"
     x-on:keydown.escape.window="if (open) { open = false; $nextTick(() => trigger?.focus()); }"
     x-on:keydown.tab.window="if (open) trap($event)"
     x-effect="syncBodyLock()">

    <div x-show="open" x-cloak class="modal" role="dialog" aria-modal="true"
         aria-labelledby="modal-{{ $name }}-title">
        <div class="modal-backdrop" x-transition.opacity
             x-on:click="open = false; $nextTick(() => trigger?.focus())" aria-hidden="true"></div>

        <div x-ref="panel" tabindex="-1"
             class="modal-content {{ $maxWidth }} p-5 relative focus:outline-none"
             style="z-index: 1000;"
             x-transition>
            <div class="flex items-start justify-between gap-3 mb-4">
                <h2 id="modal-{{ $name }}-title" class="font-serif text-lg leading-tight">{{ $title }}</h2>

                <button type="button"
                        x-on:click="open = false; $nextTick(() => trigger?.focus())"
                        class="p-1 rounded-md text-muted hover:text-charcoal hover:bg-ivory-alt transition-colors"
                        aria-label="Close dialog">
                    <x-admin.icon name="x" :size="16" />
                </button>
            </div>

            {{ $slot }}

            @if (isset($footer))
                <div class="flex flex-wrap justify-end gap-2 mt-5 pt-4 border-t border-charcoal/5">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>
