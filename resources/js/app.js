// Alpine.js + minimal interactivity
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import persist from '@alpinejs/persist';

Alpine.plugin(collapse);
Alpine.plugin(persist);

/**
 * ⌘K command palette — instant permission-aware search plus quick actions
 * and localStorage-backed recent searches. Server data contract in
 * AdminSearchController::suggest() : { orders|customers|products: [{title,meta,url}], actions: [...] }.
 */
Alpine.data('palette', ({ suggestUrl = '', exactUrl = '' }) => ({
    open: false,
    q: '',
    groups: [],
    flat: [],
    current: 0,
    actions: [],
    recent: [],
    loading: false,
    lastQuery: '',

    init() {
        // Quick actions come from the same endpoint (empty query → actions only).
        this.loadActions();
        try { this.recent = JSON.parse(localStorage.getItem('haarsingharr.recent-searches') || '[]'); }
        catch { this.recent = []; }

        // Server-rendered groups cached between keystrokes are irrelevant once
        // closed; starting out clean keeps stale results from flashing.
        this.$watch('open', (isOpen) => {
            if (isOpen) {
                this.loading = false;
                this.current = 0;
                this.$nextTick(() => this.$refs.paletteInput?.focus());
            }
        });
    },

    toggle() {
        if (this.open) {
            this.close();
            return;
        }

        this.lastFocused = document.activeElement;
        this.open = true;
        this.fetchResults();
    },

    close() {
        this.open = false;
        this.$nextTick(() => this.lastFocused?.focus?.());
    },

    async loadActions() {
        try {
            const res = await fetch(`${suggestUrl}?f=actions`, { headers: { Accept: 'application/json' } });
            if (res.ok) this.actions = (await res.json()).actions ?? [];
        } catch { /* palette still works without actions */ }
    },

    async fetchResults() {
        const term = this.q.trim();
        this.lastQuery = term;
        if (term.length < 2) { this.groups = []; this.flat = []; return; }

        this.loading = true;
        try {
            const res = await fetch(`${suggestUrl}?q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } });
            const data = res.ok ? await res.json() : {};
            const labels = { orders: 'Orders', customers: 'Customers', products: 'Products' };
            if (term !== this.lastQuery) return; // a newer keystroke already resolved
            this.groups = Object.entries(labels)
                .filter(([key]) => (data[key] ?? []).length > 0)
                .map(([key, label]) => ({ key, label, items: data[key] }));
            this.flat = this.groups.flatMap((g) => g.items);
            this.current = 0;
        } catch {
            this.groups = []; this.flat = [];
        } finally {
            this.loading = false;
        }
    },

    isCurrent(key, i) {
        return this.flat[this.current] && this.flat[this.current].url === this.groups.find((g) => g.key === key)?.items[i]?.url;
    },

    // Find the matching flat index for the hover-highlighted item.
    setFlat(key, i) {
        const item = this.groups.find((g) => g.key === key)?.items[i];
        if (item) this.current = Math.max(this.flat.findIndex((f) => f.url === item.url), 0);
    },

    move(delta) {
        if (! this.flat.length) return;
        this.current = (this.current + delta + this.flat.length) % this.flat.length;
        this.$nextTick(() =>
            document.querySelector('[role="dialog"] a.bg-champagne\\/15')?.scrollIntoView({ block: 'nearest' }));
    },

    visit() {
        if (this.flat[this.current]) {
            const item = { title: this.q.trim(), meta: this.flat[this.current].title, url: this.flat[this.current].url };
            this.remember(item);
            window.location = this.flat[this.current].url;
            return;
        }
        // Fall back to the full results page (also the no-JS behaviour).
        if (this.q.trim().length >= 2) window.location = `${this.exactUrl}?q=${encodeURIComponent(this.q.trim())}`;
    },

    remember(item) {
        try {
            this.recent = [item, ...this.recent.filter((r) => r.url !== item.url)].slice(0, 6);
            localStorage.setItem('haarsingharr.recent-searches', JSON.stringify(this.recent));
        } catch { /* private mode */ }
        this.close();
    },

    // Minimal focus trap while the dialog is open.
    trap(event) {
        if (! this.open) return;
        const dialog = this.$refs.paletteInput?.closest('[role="dialog"]');
        if (! dialog) return;
        const focusables = [...dialog.querySelectorAll('a[href], button, input')];
        const first = focusables[0];
        const last = focusables[focusables.length - 1];
        if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
        else if (! event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    },
}));


// Make Alpine available globally before DOMContentLoaded
window.Alpine = Alpine;

/**
 * x-reveal — tasteful scroll-reveal, no dependency.
 * Usage: x-reveal (fade+rise once when scrolled into view) or x-reveal="120" (stagger delay in ms).
 * Safety: elements are only hidden after Alpine initialises (no-JS stays visible),
 * reduced-motion users never see the hidden state, and one shared IntersectionObserver
 * keeps the cost flat for large product grids.
 */
document.addEventListener('alpine:init', () => {
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReduced || !('IntersectionObserver' in window)) return; // content simply stays visible

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (! entry.isIntersecting) return;
            const el = entry.target;
            const delay = parseInt(el.__revealDelay || '0', 10);
            window.setTimeout(() => {
                el.classList.remove('reveal-init');
                el.classList.add('reveal-in');
            }, delay);
            observer.unobserve(el);
        });
    }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });

    Alpine.directive('reveal', (el, { expression }) => {
        el.__revealDelay = parseInt(expression || '0', 10) || 0;
        // Elements already on screen reveal immediately via the observer callback.
        el.classList.add('reveal-init');
        observer.observe(el);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});
