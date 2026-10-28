@props([
    'action',
    'method' => 'DELETE',
    'title' => 'Are you sure?',
    'message' => 'This action cannot be undone.',
    'label' => 'Delete',
    'confirmLabel' => 'Delete',
    'variant' => 'danger',
    'name' => null,
    'showTrigger' => true,
])

@php
    $dialogId = $name ? 'confirm-' . $name : 'confirm-' . substr(md5($action . $label), 0, 8);
    $triggerClass = $variant === 'danger'
        ? 'text-xs font-medium text-danger hover:underline'
        : 'btn-outline btn-sm';
@endphp

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
             const items = [...this.$el.querySelectorAll('[role=alertdialog] button:not([disabled]), [role=alertdialog] a[href], [role=alertdialog] input:not([disabled])')].filter((item) => item.offsetParent !== null);
             if (!items.length) { event.preventDefault(); this.$refs.confirm?.focus(); return; }
             const first = items[0], last = items[items.length - 1];
             if (!items.includes(document.activeElement)) { event.preventDefault(); (event.shiftKey ? last : first).focus(); return; }
             if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
             else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
         }
     }"
     x-on:open-confirm.window="if ($event.detail === '{{ $dialogId }}') { trigger = document.activeElement; open = true; $nextTick(() => $refs.confirm?.focus()); }"
     x-init="$watch('open', value => { if (value) $nextTick(() => $refs.confirm?.focus()); })"
     x-on:keydown.escape.window="if (open) { open = false; $nextTick(() => trigger?.focus()); }"
     x-on:keydown.tab.window="if (open) trap($event)"
     x-effect="syncBodyLock()">

    @if ($showTrigger)
        <button type="button" x-ref="trigger"
                x-on:click="trigger = document.activeElement; open = true; $nextTick(() => $refs.confirm?.focus())"
                class="{{ $triggerClass }}">
            {{ $label }}
        </button>
    @endif

    <div x-show="open" x-cloak class="modal" role="alertdialog" aria-modal="true"
         aria-labelledby="{{ $dialogId }}-title" aria-describedby="{{ $dialogId }}-message">
        <div class="modal-backdrop" x-transition.opacity
             x-on:click="open = false; $nextTick(() => trigger?.focus())" aria-hidden="true"></div>

        <div class="modal-content max-w-sm p-5 relative" style="z-index: 1000;" x-transition>
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-full bg-danger-light text-danger flex items-center justify-center flex-shrink-0">
                    <x-admin.icon name="alert-triangle" :size="20" />
                </span>

                <div class="min-w-0">
                    <h2 id="{{ $dialogId }}-title" class="font-serif text-lg leading-tight">{{ $title }}</h2>
                    <p id="{{ $dialogId }}-message" class="text-sm text-muted mt-1.5">{{ $message }}</p>
                </div>
            </div>

            <form method="POST" action="{{ $action }}" class="flex justify-end gap-2 mt-5">
                @csrf
                @if (strtoupper($method) !== 'POST')
                    @method($method)
                @endif

                <button type="button" class="btn-outline btn-sm"
                        x-on:click="open = false; $nextTick(() => trigger?.focus())">
                    Cancel
                </button>

                <button type="submit" x-ref="confirm"
                        class="{{ $variant === 'danger' ? 'btn-danger btn-sm' : 'btn-gold btn-sm' }}">
                    {{ $confirmLabel }}
                </button>
            </form>
        </div>
    </div>
</div>
