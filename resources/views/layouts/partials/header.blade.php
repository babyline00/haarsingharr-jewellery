<header class="sticky top-0 z-30 bg-ivory/95 backdrop-blur-md border-b border-charcoal/5">
    <div class="container-haars">
        <div class="flex items-center justify-between h-16 md:h-20 gap-4">

            {{-- Mobile menu trigger --}}
            <button class="lg:hidden text-charcoal-soft" @click="mobileMenu = true" aria-label="Open menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>

            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 group" aria-label="Haarsingharr.pk — Jewelry For Every You">
                <img src="{{ asset('images/logo.png') }}" alt="Haarsingharr.pk" class="h-8 md:h-10 w-auto" loading="eager">
            </a>

            {{-- Desktop primary nav --}}
            <nav class="hidden lg:flex items-center gap-8 text-sm font-medium" x-data="{ open: null }">
                <a href="{{ route('shop.index') }}" class="hover:text-champagne transition-colors">Shop All</a>
                {{-- Mega menu for categories --}}
                <div class="relative" @mouseenter="open = 'categories'" @mouseleave="open = null">
                    <a href="#" class="hover:text-champagne transition-colors">Categories</a>
                    @if (isset($categories) || (\App\Models\Category::cached()))
                        @php $navCategories = \App\Models\Category::cached()->where('show_in_menu', true); @endphp
                        <div x-show="open === 'categories'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100" class="absolute top-full left-1/2 -translate-x-1/2 pt-2 w-[640px]">
                            <div class="bg-porcelain rounded-2xl shadow-xl border border-charcoal/5 p-6 grid grid-cols-3 gap-4">
                                @foreach ($navCategories as $category)
                                    <a href="{{ route('categories.show', $category) }}" class="block p-3 rounded-lg hover:bg-ivory-alt transition-colors">
                                        <div class="font-serif text-base text-charcoal">{{ $category->name }}</div>
                                        <div class="text-xs text-muted mt-0.5 line-clamp-2">{{ $category->short_description }}</div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                <a href="{{ route('shop.bridal') }}" class="hover:text-champagne transition-colors">Bridal</a>
                <a href="{{ route('shop.men') }}" class="hover:text-champagne transition-colors">Men</a>
                <a href="{{ route('shop.best_sellers') }}" class="hover:text-champagne transition-colors">Bestsellers</a>
                <a href="{{ route('shop.new_arrivals') }}" class="hover:text-champagne transition-colors">New In</a>
                <a href="{{ route('shop.sale') }}" class="text-rose hover:text-rose/80 transition-colors">Sale</a>
            </nav>

            {{-- Search & actions --}}
            <div class="flex items-center gap-3 md:gap-5">
                {{-- Search trigger --}}
                <button @click="searchOpen = true" class="text-charcoal-soft hover:text-charcoal transition-colors" aria-label="Search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16" y2="16"/></svg>
                </button>

                {{-- Account --}}
                @auth
                    <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : route('account.dashboard') }}" class="text-charcoal-soft hover:text-charcoal transition-colors" aria-label="Account">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 22a8 8 0 0 1 16 0"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-charcoal-soft hover:text-charcoal transition-colors" aria-label="Login">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 22a8 8 0 0 1 16 0"/></svg>
                    </a>
                @endauth

                {{-- Wishlist --}}
                @if (config('app.features.wishlist'))
                    <a href="{{ route('account.wishlist.index') }}" class="text-charcoal-soft hover:text-charcoal transition-colors relative" aria-label="Wishlist">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 21s-7-4.5-9.5-9.5C0.5 8 3 4 6.5 4 9 4 11 5.5 12 7c1-1.5 3-3 5.5-3C21 4 23.5 8 21.5 11.5 19 16.5 12 21 12 21z"/></svg>
                    </a>
                @endif

                {{-- Cart --}}
                @php
                    $cartCount = \App\Services\CartService::current()?->itemCount() ?? 0;
                @endphp
                <a href="{{ route('cart.index') }}" class="relative text-charcoal-soft hover:text-charcoal transition-colors" aria-label="Cart">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 5l3 .5 1.5 11a2 2 0 0 0 2 1.7h9a2 2 0 0 0 2-1.7L22 8H6"/><circle cx="9" cy="21" r="1"/><circle cx="18" cy="21" r="1"/></svg>
                    @if ($cartCount > 0)
                        <span class="absolute -top-2 -right-2 bg-champagne text-charcoal text-[10px] font-semibold rounded-full w-4 h-4 flex items-center justify-center">{{ $cartCount }}</span>
                    @endif
                </a>
            </div>
        </div>
    </div>

    {{-- Mobile menu drawer --}}
    <div x-show="mobileMenu" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-cloak>
        <div class="lg:hidden fixed inset-0 z-50">
            <div class="absolute inset-0 bg-charcoal/40" @click="mobileMenu = false"></div>
            <div class="absolute left-0 top-0 bottom-0 w-80 bg-ivory shadow-2xl p-6 overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <span class="font-serif text-xl">Menu</span>
                    <button @click="mobileMenu = false" class="text-charcoal-soft">×</button>
                </div>
                <nav class="space-y-3 text-base">
                    <a href="{{ route('shop.index') }}" class="block py-2 border-b border-charcoal/5">Shop All</a>
                    <a href="{{ route('shop.best_sellers') }}" class="block py-2 border-b border-charcoal/5">Bestsellers</a>
                    <a href="{{ route('shop.new_arrivals') }}" class="block py-2 border-b border-charcoal/5">New Arrivals</a>
                    <a href="{{ route('shop.bridal') }}" class="block py-2 border-b border-charcoal/5">Bridal Jewellery</a>
                    <a href="{{ route('shop.men') }}" class="block py-2 border-b border-charcoal/5">Men</a>
                    <a href="{{ route('shop.sale') }}" class="block py-2 border-b border-charcoal/5 text-rose">Sale</a>
                    <a href="{{ route('shop.budget') }}" class="block py-2 border-b border-charcoal/5">Shop by Budget</a>
                    <a href="{{ route('about') }}" class="block py-2 border-b border-charcoal/5">About</a>
                    <a href="{{ route('contact') }}" class="block py-2 border-b border-charcoal/5">Contact</a>
                </nav>
            </div>
        </div>
    </div>

    {{-- Search overlay --}}
    <div x-show="searchOpen" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="fixed inset-0 z-50 bg-charcoal/40 backdrop-blur-sm" @click="searchOpen = false"></div>
        <div class="absolute top-0 left-0 right-0 bg-porcelain shadow-2xl z-50 p-6">
            <form action="{{ route('search.index') }}" method="get" class="container-haars">
                <div class="flex items-center gap-3">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="text-muted"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16" y2="16"/></svg>
                    <input type="search" name="q" autofocus placeholder="Search for rings, necklaces, bracelets..." class="border-0 text-xl font-serif focus:ring-0" value="{{ request('q') }}">
                    <button type="button" @click="searchOpen = false" class="text-muted hover:text-charcoal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</header>
