<footer class="bg-charcoal text-ivory mt-16">
    <div class="container-haars py-14 md:py-20">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 md:gap-12">

            {{-- Brand & description --}}
            <div class="col-span-2 md:col-span-1">
                <div class="mb-3">
                    <img src="{{ $brand['logo'] ?? asset('images/logo.png') }}" alt="{{ $brand['name'] ?? 'Haarsingharr' }}" style="height: {{ min($brand['logoHeight'] ?? 56, 56) }}px; width: auto;" class="w-auto brightness-0 invert">
                </div>
                <p class="text-ivory/70 text-sm leading-relaxed mb-4">
                    Jewelry • For Every You. Hand-crafted 22k gold and diamond jewellery for the
                    Pakistani connoisseur — designed to last generations.
                </p>
                <div class="flex gap-3">
                    @if (config('storefront.social.instagram'))
                        <a href="{{ config('storefront.social.instagram') }}" target="_blank" rel="noopener" class="text-ivory/60 hover:text-champagne transition-colors" aria-label="Instagram">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>
                        </a>
                    @endif
                    @if (config('storefront.social.facebook'))
                        <a href="{{ config('storefront.social.facebook') }}" target="_blank" rel="noopener" class="text-ivory/60 hover:text-champagne transition-colors" aria-label="Facebook">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </a>
                    @endif
                </div>
            </div>

            {{-- Shop --}}
            <div>
                <h3 class="text-champagne text-xs font-semibold uppercase tracking-widest mb-4">Shop</h3>
                <ul class="space-y-2 text-sm text-ivory/70">
                    <li><a href="{{ route('shop.index') }}" class="hover:text-ivory transition-colors">All Jewellery</a></li>
                    <li><a href="{{ route('shop.bridal') }}" class="hover:text-ivory transition-colors">Bridal</a></li>
                    <li><a href="{{ route('shop.men') }}" class="hover:text-ivory transition-colors">For Men</a></li>
                    <li><a href="{{ route('shop.best_sellers') }}" class="hover:text-ivory transition-colors">Bestsellers</a></li>
                    <li><a href="{{ route('shop.new_arrivals') }}" class="hover:text-ivory transition-colors">New Arrivals</a></li>
                    <li><a href="{{ route('shop.sale') }}" class="hover:text-ivory transition-colors">Sale</a></li>
                </ul>
            </div>

            {{-- Help --}}
            <div>
                <h3 class="text-champagne text-xs font-semibold uppercase tracking-widest mb-4">Customer Care</h3>
                <ul class="space-y-2 text-sm text-ivory/70">
                    <li><a href="{{ route('pages.show', 'shipping-policy') }}" class="hover:text-ivory transition-colors">Shipping Policy</a></li>
                    <li><a href="{{ route('pages.show', 'exchange-policy') }}" class="hover:text-ivory transition-colors">Exchange & Refund</a></li>
                    <li><a href="{{ route('pages.show', 'size-guides') }}" class="hover:text-ivory transition-colors">Size Guide</a></li>
                    <li><a href="{{ route('pages.show', 'jewellery-care') }}" class="hover:text-ivory transition-colors">Jewellery Care</a></li>
                    <li><a href="{{ route('faq') }}" class="hover:text-ivory transition-colors">FAQ</a></li>
                    <li><a href="{{ route('contact') }}" class="hover:text-ivory transition-colors">Contact</a></li>
                    <li><a href="{{ route('order.tracking.form') }}" class="hover:text-ivory transition-colors">Track Order</a></li>
                </ul>
            </div>

            {{-- About --}}
            <div>
                <h3 class="text-champagne text-xs font-semibold uppercase tracking-widest mb-4">About</h3>
                <ul class="space-y-2 text-sm text-ivory/70">
                    <li><a href="{{ route('about') }}" class="hover:text-ivory transition-colors">Our Story</a></li>
                    <li><a href="{{ route('pages.show', 'privacy-policy') }}" class="hover:text-ivory transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('pages.show', 'terms') }}" class="hover:text-ivory transition-colors">Terms & Conditions</a></li>
                    <li><a href="{{ route('locations') }}" class="hover:text-ivory transition-colors">Our Stores</a></li>
                </ul>
            </div>
        </div>

        {{-- Newsletter --}}
        @if (config('app.features.newsletter'))
            <div class="border-t border-ivory/10 mt-12 pt-10 flex flex-col md:flex-row gap-6 items-center justify-between">
                <div>
                    <h3 class="font-serif text-xl md:text-2xl mb-1">Join the Haarsingharr Circle</h3>
                    <p class="text-sm text-ivory/60">Be the first to discover new collections and private events.</p>
                </div>
                <form action="{{ route('newsletter.subscribe') }}" method="post" class="flex gap-2 w-full md:w-auto max-w-md">
                    @csrf
                    <input type="email" name="email" required placeholder="Your email address"
                           class="flex-1 md:w-72 bg-ivory/5 border border-ivory/20 rounded-md px-4 py-2.5 text-ivory placeholder-ivory/40 focus:border-champagne focus:ring-1 focus:ring-champagne">
                    <button type="submit" class="btn-gold">Subscribe</button>
                </form>
            </div>
        @endif

        {{-- Bottom bar --}}
        <div class="border-t border-ivory/10 mt-10 pt-6 flex flex-col md:flex-row justify-between items-center text-xs text-ivory/50 gap-3">
            <div>© {{ date('Y') }} {{ config('storefront.legal.company_name', 'Haarsingharr Jewellers') }}. All rights reserved.</div>
            <div class="flex gap-4">
                <span>Cash on Delivery</span>
                <span>•</span>
                <span>SSL Secured</span>
                <span>•</span>
                <span>Made in Pakistan</span>
            </div>
        </div>
    </div>
</footer>
