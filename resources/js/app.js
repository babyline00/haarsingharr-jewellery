// Alpine.js + minimal interactivity
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import persist from '@alpinejs/persist';

Alpine.plugin(collapse);
Alpine.plugin(persist);

// Make Alpine available globally before DOMContentLoaded
window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});

// Lightweight helpers (no frameworks — keep this minimal)
window.Haarsingharr = {
    formatPrice(amount) {
        return 'Rs. ' + new Intl.NumberFormat('en-PK').format(Math.round(amount));
    },

    async fetchCartCount() {
        const res = await fetch('/cart/count', { headers: { 'Accept': 'application/json' } });
        if (res.ok) {
            const data = await res.json();
            document.querySelectorAll('[data-cart-count]').forEach(el => {
                el.textContent = data.count;
                el.classList.toggle('hidden', data.count === 0);
            });
        }
    },

    initProductGallery() {
        const thumbs = document.querySelectorAll('[data-thumb]');
        const main = document.querySelector('[data-main-image]');
        if (! main || ! thumbs.length) return;
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                const src = thumb.getAttribute('data-src');
                main.setAttribute('src', src);
                thumbs.forEach(t => t.classList.remove('border-champagne'));
                thumb.classList.add('border-champagne');
            });
        });
    },
};

document.addEventListener('DOMContentLoaded', () => {
    window.Haarsingharr.initProductGallery();
});
