<header class="sticky top-0 z-[var(--z-sticky)] border-b border-charcoal/8 transition-all duration-300"
        x-data="{ mobileMenu: false, searchOpen: false, scrolled: false }"
        x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 10 })"
        :class="scrolled ? 'bg-ivory shadow-md' : 'bg-ivory/98 backdrop-blur-lg shadow-sm'">
    <div class="container-haars">
        <div class="flex items-center justify-between h-16 md:h-20 gap-4">

            {{-- Mobile menu trigger --}}
            <button class="lg:hidden flex items-center justify-center w-10 h-10 text-charcoal-soft hover:text-charcoal hover:bg-charcoal/5 rounded-md transition-all duration-1000"
                    @click="mobileMenu = true"
                    aria-label="Open menu"
                    aria-expanded="false"
                    :aria-expanded="mobileMenu.toString()"
                    aria-controls="mobile-menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" :class="{ 'rotate-90': mobileMenu }" class="transition-transform duration-1000">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 group flex-shrink-0" aria-label="Haarsingharr.pk — Jewelry For Every You">
                <img src="{{ asset('images/logo.png') }}" alt="Haarsingharr.pk"
                     class="w-auto max-h-[55px] md:max-h-[80px] lg:max-h-[80px]"
                     loading="eager" width="120" height="40">
            </a>

            {{-- Desktop primary nav --}}
            <nav class="hidden lg:flex items-center gap-6 md:gap-8 text-sm font-medium" x-data="{ open: null }" aria-label="Main navigation">
                <a href="{{ route('shop.index') }}" class="relative overflow-hidden group hover:text-champagne transition-colors duration-1000 whitespace-nowrap">
                    <span class="inline-block transition-transform duration-1000 group-hover:translate-x-1">Shop All</span>
                </a>
                {{-- Mega menu for categories --}}
                <div class="relative" @mouseenter="open = 'categories'" @mouseleave="open = null">
                    <a href="#" class="relative overflow-hidden group hover:text-champagne transition-colors duration-1000 whitespace-nowrap">
                        <span class="inline-block transition-transform duration-1000 group-hover:translate-x-1">Categories</span>
                    </a>
                    @if (isset($categories) || (\App\Models\Category::cached()))
                        @php $navCategories = \App\Models\Category::cached()->where('show_in_menu', true); @endphp
                        <div x-show="open === 'categories'" x-transition:enter="transition ease-out duration-1000" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100"
                             class="absolute top-full left-1/2 -translate-x-1/2 pt-2 w-[640px]">
                            <div class="bg-porcelain rounded-2xl shadow-xl border border-charcoal/5 p-6 grid grid-cols-3 gap-4">
                                @foreach ($navCategories as $category)
                                    <a href="{{ route('categories.show', $category) }}" class="block p-3 rounded-lg hover:bg-ivory-alt transition-all duration-1000 group">
                                        <div class="font-serif text-base text-charcoal group-hover:translate-x-1 transition-transform duration-1000">{{ $category->name }}</div>
                                        <div class="text-xs text-muted mt-0.5 line-clamp-2 group-hover:translate-x-1 transition-transform duration-1000">{{ $category->short_description }}</div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <a href="{{ route('shop.bridal') }}" class="relative overflow-hidden group hover:text-champagne transition-colors duration-1000 whitespace-nowrap">
                    <span class="inline-block transition-transform duration-1000 group-hover:translate-x-1">Bridal</span>
                </a>
                <a href="{{ route('shop.men') }}" class="relative overflow-hidden group hover:text-champagne transition-colors duration-1000 whitespace-nowrap">
                    <span class="inline-block transition-transform duration-1000 group-hover:translate-x-1">Men</span>
                </a>
                <a href="{{ route('shop.best_sellers') }}" class="relative overflow-hidden group hover:text-champagne transition-colors duration-1000 whitespace-nowrap">
                    <span class="inline-block transition-transform duration-1000 group-hover:translate-x-1">Bestsellers</span>
                </a>
                <a href="{{ route('shop.new_arrivals') }}" class="relative overflow-hidden group hover:text-champagne transition-colors duration-1000 whitespace-nowrap">
                    <span class="inline-block transition-transform duration-1000 group-hover:translate-x-1">New In</span>
                </a>
                <a href="{{ route('shop.sale') }}" class="relative overflow-hidden group text-rose-deep hover:text-rose-deep/80 transition-colors duration-1000 whitespace-nowrap">
                    <span class="inline-block transition-transform duration-1000 group-hover:translate-x-1">Sale</span>
                </a>
            </nav>

            {{-- Search & actions --}}
            <div class="flex items-center gap-3 md:gap-5">
                {{-- Search trigger --}}
                <button @click="searchOpen = true"
                        class="lg:hidden flex items-center justify-center w-10 h-10 text-charcoal-soft hover:text-charcoal hover:bg-charcoal/5 rounded-md transition-all duration-1000"
                        aria-label="Search"
                        aria-expanded="false"
                        :aria-expanded="searchOpen.toString()"
                        aria-controls="search-overlay">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="transition-transform duration-1000 hover:scale-110"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16" y2="16"/></svg>
                </button>

                {{-- Account --}}
                @auth
                    <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : route('account.dashboard') }}"
                       class="hidden lg:flex items-center justify-center w-10 h-10 text-charcoal-soft hover:text-charcoal hover:bg-charcoal/5 rounded-md transition-all duration-1000"
                       aria-label="Account">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="transition-transform duration-1000 hover:scale-110"><circle cx="12" cy="8" r="4"/><path d="M4 22a8 8 0 0 1 16 0"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}"
                       class="hidden lg:flex items-center justify-center w-10 h-10 text-charcoal-soft hover:text-charcoal hover:bg-charcoal/5 rounded-md transition-all duration-1000"
                       aria-label="Login">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="transition-transform duration-1000 hover:scale-110"><circle cx="12" cy="8" r="4"/><path d="M4 22a8 8 0 0 1 16 0"/></svg>
                    </a>
                @endauth

                {{-- Wishlist --}}
                @if (config('app.features.wishlist'))
                    <a href="{{ route('account.wishlist.index') }}"
                       class="hidden lg:flex items-center justify-center w-10 h-10 text-charcoal-soft hover:text-charcoal hover:bg-charcoal/5 rounded-md transition-all duration-1000 relative"
                       aria-label="Wishlist">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="transition-transform duration-1000 hover:scale-110"><path d="M12 21s-7-4.5-9.5-9.5C0.5 8 3 4 6.5 4 9 4 11 5.5 12 7c1-1.5 3-3 5.5-3C21 4 23.5 8 21.5 11.5 19 16.5 12 21 12 21z"/></svg>
                    </a>
                @endif

                {{-- Cart --}}
                @php
                    $cartCount = app(\App\Services\CartService::class)->current()?->itemCount() ?? 0;
                @endphp
                <a href="{{ route('cart.index') }}"
                   class="flex items-center justify-center w-10 h-10 lg:w-auto lg:h-auto lg:px-4 lg:py-2 relative text-charcoal-soft hover:text-charcoal hover:bg-charcoal/5 rounded-md transition-all duration-1000"
                   aria-label="Shopping cart">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="lg:hidden transition-transform duration-1000 hover:scale-110"><path d="M3 5l3 .5 1.5 11a2 2 0 0 0 2 1.7h9a2 2 0 0 0 2-1.7L22 8H6"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg>
                    <span class="hidden lg:inline-flex items-center gap-1.5 transition-transform duration-1000 hover:translate-x-1">Cart</span>
                    @if ($cartCount > 0)
                        <span class="absolute -top-1.5 -right-1.5 bg-champagne text-charcoal text-[10px] font-semibold rounded-full w-5 h-5 flex items-center justify-center transition-transform duration-1000 hover:scale-110">{{ $cartCount }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>

    {{-- Mobile menu drawer --}}
    <div x-show="mobileMenu" x-cloak id="mobile-menu" role="dialog" aria-modal="true" aria-labelledby="mobile-menu-title" class="lg:hidden">
        <div class="fixed inset-0 z-[var(--z-fixed)]">
            <div class="absolute inset-0 bg-charcoal/50 backdrop-blur-sm transition-opacity duration-300"
                 @click="mobileMenu = false"
                 aria-hidden="true"
                 x-show="mobileMenu"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"></div>
            <div class="absolute right-0 top-0 bottom-0 w-full sm:max-w-sm bg-ivory shadow-2xl overflow-y-auto"
                 x-show="mobileMenu"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="translate-x-full">
                <div class="flex items-center justify-between p-5 border-b border-charcoal/5">
                    <h2 id="mobile-menu-title" class="font-serif text-xl">Menu</h2>
                    <button @click="mobileMenu = false"
                            class="flex items-center justify-center w-10 h-10 text-charcoal-soft hover:text-charcoal hover:bg-charcoal/5 rounded-md transition-colors"
                            aria-label="Close menu">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <nav class="p-5 space-y-0" aria-label="Mobile navigation">
                    <a href="{{ route('shop.index') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        <span>Shop All</span>
                    </a>

                    <div x-data="{ catOpen: false }">
                        <button @click="catOpen = !catOpen"
                                class="w-full flex items-center justify-between py-3 border-b border-charcoal/5 text-left text-base font-medium transition-all duration-1000 hover:translate-x-2 hover:text-champagne"
                                aria-expanded="false" :aria-expanded="catOpen.toString()" aria-controls="mobile-categories">
                            <span class="flex items-center gap-3">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                                Categories
                            </span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="transition-transform duration-200" :class="{ 'rotate-180': catOpen }">
                                <polyline points="6 9 12 15 18 9"/>
                            </svg>
                        </button>
                        <div x-show="catOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100" id="mobile-categories" class="pl-4 pb-2 space-y-1">
                            @if (isset($categories) || (\App\Models\Category::cached()))
                                @php $navCategories = \App\Models\Category::cached()->where('show_in_menu', true); @endphp
                                @foreach ($navCategories as $category)
                                    <a href="{{ route('categories.show', $category) }}" class="flex items-center gap-3 py-2 text-sm text-charcoal-soft hover:text-charcoal hover:bg-ivory-alt rounded-md px-2 transition-colors">
                                        @if ($category->icon)
                                            <img src="{{ \App\Support\Media::url($category->icon) }}" alt="" class="w-5 h-5 object-contain flex-shrink-0">
                                        @else
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/></svg>
                                        @endif
                                        <span>{{ $category->name }}</span>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('shop.bridal') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        <span>Bridal</span>
                    </a>
                        <a href="{{ route('shop.men') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><circle cx="12" cy="8" r="4"/><path d="M20 22v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/></svg>
                        <span>Men</span>
                    </a>
                        <a href="{{ route('shop.best_sellers') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>Bestsellers</span>
                    </a>
                        <a href="{{ route('shop.new_arrivals') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.49 8.49l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.49-8.49l2.83-2.83"/></svg>
                        <span>New In</span>
                    </a>
                        <a href="{{ route('shop.sale') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 text-rose-deep transition-all duration-1000 hover:translate-x-2 hover:text-rose-deep/80">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        <span>Sale</span>
                    </a>
                        <a href="{{ route('shop.budget') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <span>Shop by Budget</span>
                    </a>

                        <a href="{{ route('about') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span>About</span>
                    </a>
                        <a href="{{ route('contact') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span>Contact</span>
                    </a>

                        @auth
                            <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : route('account.dashboard') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>{{ auth()->user()->isStaff() ? 'Admin Dashboard' : 'My Account' }}</span>
                    </a>
                            <a href="{{ route('account.orders.index') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                        <span>My Orders</span>
                    </a>
                            @if (config('app.features.wishlist'))
                                <a href="{{ route('account.wishlist.index') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M12 21s-7-4.5-9.5-9.5C0.5 8 3 4 6.5 4 9 4 11 5.5 12 7c1-1.5 3-3 5.5-3C21 4 23.5 8 21.5 11.5 19 16.5 12 21 12 21z"/></svg>
                        <span>Wishlist</span>
                    </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        <span>Login</span>
                    </a>
                            <a href="{{ route('register') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                        <span>Register</span>
                    </a>
                        @endauth

                        @php
                            $cartCount = app(\App\Services\CartService::class)->current()?->itemCount() ?? 0;
                        @endphp
                        <a href="{{ route('cart.index') }}" class="flex items-center gap-3 py-3 border-b border-charcoal/5 transition-all duration-1000 hover:translate-x-2 hover:text-champagne">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><path d="M3 5l3 .5 1.5 11a2 2 0 0 0 2 1.7h9a2 2 0 0 0 2-1.7L22 8H6"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg>
                            <span>Cart</span>
                            @if ($cartCount > 0)
                                <span class="bg-champagne text-charcoal text-[10px] font-semibold rounded-full w-5 h-5 flex items-center justify-center ml-auto">{{ $cartCount }}</span>
                            @endif
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </div>

    {{-- Search overlay --}}
    <div x-show="searchOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" id="search-overlay" role="dialog" aria-modal="true" aria-labelledby="search-title" class="z-[var(--z-modal)]">
        <div class="fixed inset-0 z-[var(--z-modal-backdrop)] bg-charcoal/50 backdrop-blur-sm" @click="searchOpen = false" aria-hidden="true"></div>
        <div class="fixed inset-x-0 top-0 z-[var(--z-modal)] bg-porcelain shadow-2xl p-4 md:p-6">
            <form action="{{ route('search.index') }}" method="get" class="container-haars" x-data="{
                query: '{{ request('q') }}',
                results: [],
                loading: false,
                showResults: false,
                async search() {
                    if (this.query.length < 2) {
                        this.results = [];
                        this.showResults = false;
                        return;
                    }
                    this.loading = true;
                    try {
                        const response = await fetch('/search/suggest?q=' + encodeURIComponent(this.query), {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();
                        this.results = data.results || [];
                        this.showResults = true;
                    } catch (e) {
                        this.results = [];
                        this.showResults = false;
                    }
                    this.loading = false;
                },
                debounceTimer: null,
                debouncedSearch() {
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => this.search(), 300);
                }
            }" @submit.prevent="if (query.trim()) window.location.href = '{{ route('search.index') }}?q=' + encodeURIComponent(query)">
                <div class="flex items-center gap-3">
                    <h2 id="search-title" class="sr-only">Search</h2>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-muted flex-shrink-0"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16" y2="16"/></svg>
                    <input type="search" name="q" x-model="query" @input="debouncedSearch()" autofocus placeholder="Search for rings, necklaces, bracelets..."
                           class="border-0 text-lg md:text-xl font-serif focus:ring-0 flex-1 min-w-0"
                           value="{{ request('q') }}"
                           aria-label="Search query">
                    <button type="button" @click="searchOpen = false"
                            class="flex items-center justify-center w-10 h-10 text-muted hover:text-charcoal hover:bg-charcoal/5 rounded-md transition-colors flex-shrink-0"
                            aria-label="Cancel search">
                        Cancel
                    </button>
                </div>

                {{-- Live search results --}}
                <div x-show="showResults && results.length > 0" x-cloak class="mt-4 border-t border-charcoal/10 pt-4">
                    <div class="space-y-2 max-h-[400px] overflow-y-auto">
                        <template x-for="result in results" :key="result.id">
                            <a :href="result.url" class="flex items-center gap-3 p-2 rounded-lg hover:bg-ivory-alt/50 transition-colors">
                                <div class="w-12 h-12 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                                    <img :src="result.image" :alt="result.name" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-serif text-sm text-charcoal truncate" x-text="result.name"></p>
                                    <p class="text-xs text-muted" x-text="result.category"></p>
                                </div>
                                <div class="text-sm font-medium text-champagne-deep whitespace-nowrap" x-text="result.formatted_price"></div>
                            </a>
                        </template>
                    </div>
                    <div class="mt-3 pt-3 border-t border-charcoal/5">
                        <a :href="'{{ route('search.index') }}?q=' + encodeURIComponent(query)" class="block text-center text-sm text-champagne-dark hover:underline">
                            View all results →
                        </a>
                    </div>
                </div>

                {{-- No results --}}
                <div x-show="showResults && results.length === 0 && query.length >= 2" x-cloak class="mt-4 border-t border-charcoal/10 pt-4">
                    <p class="text-sm text-muted text-center py-4">No pieces found for "<span x-text="query"></span>". Try a different term.</p>
                </div>

                {{-- Loading --}}
                <div x-show="loading" x-cloak class="mt-4 border-t border-charcoal/10 pt-4">
                    <div class="flex items-center justify-center py-4">
                        <div class="spinner spinner-sm"></div>
                        <span class="text-sm text-muted ml-2">Searching...</span>
                    </div>
                </div>
            </form>
        </div>
    </div>
</header>