<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name') . ' Admin')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @if (! app()->environment('production'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('build/app.css') }}">
        <script src="{{ asset('build/app.js') }}" defer></script>
    @endif

    @stack('styles')
</head>
<body class="min-h-screen bg-ivory-alt/30 text-charcoal" x-data="{ sidebar: false }">

    {{-- Top bar --}}
    <header class="bg-charcoal text-ivory sticky top-0 z-30">
        <div class="flex items-center justify-between h-14 px-4 md:px-6">
            <div class="flex items-center gap-3">
                <button @click="sidebar = !sidebar" class="lg:hidden text-ivory" aria-label="Toggle sidebar">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Haarsingharr.pk Admin" class="h-7 w-auto brightness-0 invert opacity-90">
                </a>
            </div>
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('home') }}" target="_blank" class="text-ivory/70 hover:text-champagne transition-colors hidden md:flex items-center gap-1">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    View store
                </a>

                {{-- User menu --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 text-ivory hover:text-champagne transition-colors">
                        <div class="w-8 h-8 rounded-full bg-champagne/30 flex items-center justify-center text-xs font-semibold">
                            {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
                        </div>
                        <span class="hidden md:block">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-48 bg-porcelain text-charcoal rounded-md shadow-lg py-1">
                        <a href="{{ route('account.profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-ivory-alt transition-colors">Profile</a>
                        @can('audit.view')
                            <a href="{{ route('admin.active-sessions.index') }}" class="block px-4 py-2 text-sm hover:bg-ivory-alt transition-colors">Active Sessions</a>
                        @endcan
                        <form action="{{ route('logout') }}" method="post" class="block">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-ivory-alt transition-colors text-danger">Sign Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">

        {{-- Sidebar --}}
        <aside class="lg:block bg-porcelain border-r border-charcoal/5 w-64 min-h-[calc(100vh-56px)]"
               :class="sidebar ? 'block fixed z-40 top-14 left-0 bottom-0' : 'hidden lg:block'">
            <nav class="p-3 space-y-1 text-sm">

                <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.dashboard') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">
                    <span class="inline-flex items-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                        Dashboard
                    </span>
                </a>

                {{-- Catalogue --}}
                @can('products.view')
                <div class="pt-3 pb-1 px-3 text-xs uppercase tracking-widest text-muted">Catalogue</div>
                <a href="{{ route('admin.products.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.products.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Products</a>
                <a href="{{ route('admin.categories.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.categories.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Categories</a>
                <a href="{{ route('admin.collections.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.collections.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Collections</a>
                <a href="{{ route('admin.inventory.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.inventory.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Inventory</a>
                @endcan

                {{-- Sales --}}
                @can('orders.view')
                <div class="pt-3 pb-1 px-3 text-xs uppercase tracking-widest text-muted">Sales</div>
                <a href="{{ route('admin.orders.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.orders.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Orders</a>
                @endcan
                @can('payments.view')
                <a href="{{ route('admin.payments.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.payments.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Payments</a>
                @endcan
                @can('refunds.view')
                <a href="{{ route('admin.refunds.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.refunds.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Refunds</a>
                @endcan
                @can('exchanges.view')
                <a href="{{ route('admin.exchanges.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.exchanges.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Exchanges / Returns</a>
                @endcan

                {{-- Customers --}}
                @can('customers.view')
                <div class="pt-3 pb-1 px-3 text-xs uppercase tracking-widest text-muted">Customers</div>
                <a href="{{ route('admin.customers.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.customers.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Customers</a>
                @endcan
                @can('reviews.view')
                <a href="{{ route('admin.reviews.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.reviews.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Reviews</a>
                @endcan

                {{-- Marketing --}}
                @can('coupons.view')
                <div class="pt-3 pb-1 px-3 text-xs uppercase tracking-widest text-muted">Marketing</div>
                <a href="{{ route('admin.coupons.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.coupons.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Coupons</a>
                @endcan
                @can('banners.view')
                <a href="{{ route('admin.banners.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.banners.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Banners</a>
                <a href="{{ route('admin.homepage-sections.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.homepage-sections.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Homepage Builder</a>
                @endcan
                @can('cms.view')
                <a href="{{ route('admin.pages.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.pages.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">CMS Pages</a>
                <a href="{{ route('admin.faqs.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.faqs.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">FAQs</a>
                @endcan

                {{-- System --}}
                @can('staff.view')
                <div class="pt-3 pb-1 px-3 text-xs uppercase tracking-widest text-muted">System</div>
                <a href="{{ route('admin.staff.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.staff.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Staff</a>
                @endcan
                @can('roles.view')
                <a href="{{ route('admin.roles.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.roles.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Roles & Permissions</a>
                @endcan
                @can('reports.view')
                <a href="{{ route('admin.reports.sales') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.reports.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Reports</a>
                @endcan
                @can('settings.view')
                <a href="{{ route('admin.settings.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.settings.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Store Settings</a>
                @endcan
                @can('audit.view')
                <a href="{{ route('admin.audit-logs.index') }}" class="block px-3 py-2 rounded-md {{ request()->routeIs('admin.audit-logs.*') ? 'bg-champagne/15 text-champagne-dark font-medium' : 'text-charcoal-soft hover:bg-ivory-alt' }}">Audit Logs</a>
                @endcan
            </nav>
        </aside>

        {{-- Backdrop for mobile sidebar --}}
        <div x-show="sidebar" @click="sidebar = false" x-transition.opacity class="lg:hidden fixed inset-0 top-14 bg-charcoal/30 z-30" x-cloak></div>

        {{-- Main content --}}
        <main class="flex-1 p-4 md:p-6 lg:p-8">
            @if (session('success'))
                <div class="bg-success/10 border border-success/30 text-success px-4 py-3 rounded-md text-sm mb-4" x-data x-init="setTimeout(() => $el.remove(), 5000)">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="bg-danger/10 border border-danger/30 text-danger px-4 py-3 rounded-md text-sm mb-4">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
