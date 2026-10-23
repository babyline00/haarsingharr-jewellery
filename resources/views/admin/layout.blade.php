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
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <style>
        .ck.ck-editor { width: 100%; }
        .ck-editor__editable { min-height: 150px; background: #fff; }
        .ck.ck-editor__main > .ck-editor__editable:not(.ck-focused) { border-color: rgba(0, 0, 0, .15); }
    </style>
</head>
<body class="min-h-screen bg-ivory-alt/30 text-charcoal" x-data="{ sidebar: false }">

    {{-- Top bar --}}
    <header class="bg-charcoal text-ivory sticky top-0 z-[var(--z-sticky)] border-b border-charcoal/10">
        <div class="flex items-center justify-between h-16 px-4 md:px-6">
            <div class="flex items-center gap-3">
                <button @click="sidebar = !sidebar" class="lg:hidden p-2 text-ivory/80 hover:text-ivory rounded-md hover:bg-charcoal/20 transition-colors" aria-label="Toggle sidebar" aria-expanded="false" :aria-expanded="sidebar.toString()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="{ 'rotate-90': sidebar }" class="transition-transform duration-200">
                        <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                    </svg>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center" aria-label="{{ $brand['name'] ?? 'Haarsingharr' }} Admin Dashboard">
                    <img src="{{ $brand['logo'] ?? asset('images/logo.png') }}" alt="{{ $brand['name'] ?? 'Haarsingharr' }}" style="height: {{ min($brand['logoHeight'] ?? 56, 36) }}px; width: auto;" class="w-auto brightness-0 invert opacity-90">
                </a>
            </div>
            <div class="flex items-center gap-3 text-sm">
                {{-- Search --}}
                <div class="hidden lg:block relative">
                    <label for="global-search" class="sr-only">Search admin</label>
                    <input type="search" id="global-search" placeholder="Search… (⌘K)" class="w-64 bg-charcoal/30 border border-charcoal/20 text-ivory placeholder-charcoal/40 rounded-md px-4 py-2 text-sm focus:bg-charcoal/40 focus:border-champagne/50 focus:outline-none focus:ring-1 focus:ring-champagne transition-all duration-200" x-data="{ open: false }" @focus="open = true" @keydown.escape="open = false">
                </div>

                <a href="{{ route('home') }}" target="_blank" class="text-ivory/70 hover:text-champagne transition-colors hidden md:flex items-center gap-1.5 p-2 rounded-md hover:bg-charcoal/20" title="View storefront">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>View store</span>
                </a>

                {{-- Notifications --}}
                <div class="relative" x-data="{ open: false, count: {{ auth()->user()->unreadNotifications()->count() ?? 0 }} }">
                    <button @click="open = !open" class="p-2 text-ivory/80 hover:text-ivory rounded-md hover:bg-charcoal/20 transition-colors relative" aria-label="Notifications" :aria-expanded="open.toString()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span x-show="count > 0" x-text="count" class="absolute -top-1 -right-1 w-5 h-5 text-[10px] font-semibold bg-danger text-ivory rounded-full flex items-center justify-center"></span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-80 bg-porcelain text-charcoal rounded-xl shadow-xl border border-charcoal/5 py-1 overflow-hidden">
                        <div class="px-4 py-3 border-b border-charcoal/5 flex items-center justify-between">
                            <h3 class="font-medium">Notifications</h3>
                            <form action="{{ route('admin.notifications.read-all') }}" method="post">
                                @csrf
                                <button type="submit" class="text-xs text-champagne-dark hover:underline">Mark all read</button>
                            </form>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            @foreach (auth()->user()->unreadNotifications()->take(10)->get() as $notification)
                                <a href="{{ $notification->data['url'] ?? $notification->data['action_url'] ?? '#' }}" class="block px-4 py-3 hover:bg-ivory-alt/50 border-b border-charcoal/5 transition-colors">
                                    <p class="text-sm font-medium">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    @if (! empty($notification->data['message']))
                                        <p class="text-xs text-charcoal-soft mt-0.5 line-clamp-2">{{ $notification->data['message'] }}</p>
                                    @endif
                                    <p class="text-xs text-muted mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            @endforeach
                            <a href="{{ route('admin.notifications.index') }}" class="block px-4 py-3 text-center text-xs text-champagne-dark hover:bg-ivory-alt transition-colors">View all</a>
                        </div>
                    </div>
                </div>

                {{-- User menu --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2.5 p-1.5 rounded-md text-ivory/80 hover:text-ivory hover:bg-charcoal/20 transition-colors" aria-label="User menu" :aria-expanded="open.toString()">
                        <div class="w-8 h-8 rounded-full bg-champagne/30 flex items-center justify-center text-xs font-semibold">
                            {{ strtoupper(substr(auth()->user()->first_name, 0, 1) . substr(auth()->user()->last_name, 0, 1)) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                            <p class="text-[11px] text-ivory/50">{{ auth()->user()->getRoleNames()->first() ?? 'Staff' }}</p>
                        </div>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ml-1"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition class="absolute right-0 mt-2 w-52 bg-porcelain text-charcoal rounded-xl shadow-xl border border-charcoal/5 py-1 overflow-hidden">
                        <div class="px-4 py-3 border-b border-charcoal/5">
                            <p class="text-sm font-medium">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                            <p class="text-xs text-muted">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('account.profile.edit') }}" class="dropdown-item">Profile</a>
                        <a href="#" class="dropdown-item">Change Password</a>
                        @can('audit.view')
                            <a href="{{ route('admin.active-sessions.index') }}" class="dropdown-item">Active Sessions</a>
                        @endcan
                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="post" class="block">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger w-full text-left">Sign Out</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">

        {{-- Mobile sidebar backdrop --}}
        <div x-show="sidebar" @click="sidebar = false" x-transition.opacity class="lg:hidden fixed inset-0 top-16 bg-charcoal/40 z-[var(--z-fixed)]" x-cloak aria-hidden="true"></div>

        {{-- Sidebar --}}
        <aside class="lg:block bg-porcelain border-r border-charcoal/5 w-64 min-h-[calc(100vh-64px)] transition-transform duration-300 ease-out"
               :class="sidebar ? 'translate-x-0 fixed z-[var(--z-fixed)] top-16 left-0 bottom-0' : '-translate-x-full lg:translate-x-0'">
            <div class="flex flex-col h-full">
                <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto" role="navigation" aria-label="Main navigation">
                    {{-- Dashboard --}}
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="flex-shrink-0"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="16" width="7" height="5" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                        <span>Dashboard</span>
                    </a>

                    {{-- Catalogue --}}
                    @can('products.view')
                    <div class="sidebar-section-title">Catalogue</div>
                    <a href="{{ route('admin.products.index') }}" class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">Products</a>
                    <a href="{{ route('admin.categories.index') }}" class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Categories</a>
                    <a href="{{ route('admin.collections.index') }}" class="sidebar-link {{ request()->routeIs('admin.collections.*') ? 'active' : '' }}">Collections</a>
                    <a href="{{ route('admin.inventory.index') }}" class="sidebar-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">Inventory</a>
                    @endcan

                    {{-- Sales --}}
                    @can('orders.view')
                    <div class="sidebar-section-title">Sales</div>
                    <a href="{{ route('admin.orders.index') }}" class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">Orders</a>
                    @endcan
                    @can('payments.view')
                    <a href="{{ route('admin.payments.index') }}" class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">Payments</a>
                    @endcan
                    @can('refunds.view')
                    <a href="{{ route('admin.refunds.index') }}" class="sidebar-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">Refunds</a>
                    @endcan
                    @can('exchanges.view')
                    <a href="{{ route('admin.exchanges.index') }}" class="sidebar-link {{ request()->routeIs('admin.exchanges.*') ? 'active' : '' }}">Exchanges / Returns</a>
                    @endcan

                    {{-- Customers --}}
                    @can('customers.view')
                    <div class="sidebar-section-title">Customers</div>
                    <a href="{{ route('admin.customers.index') }}" class="sidebar-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">Customers</a>
                    @endcan
                    @can('reviews.view')
                    <a href="{{ route('admin.reviews.index') }}" class="sidebar-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">Reviews</a>
                    @endcan

                    {{-- Marketing --}}
                    @can('coupons.view')
                    <div class="sidebar-section-title">Marketing</div>
                    <a href="{{ route('admin.coupons.index') }}" class="sidebar-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}">Coupons</a>
                    <a href="{{ route('admin.gifts.index') }}" class="sidebar-link {{ request()->routeIs('admin.gifts.*') ? 'active' : '' }}">Gift Popup</a>
                    <a href="{{ route('admin.giveaways.index') }}" class="sidebar-link {{ request()->routeIs('admin.giveaways.*') ? 'active' : '' }}">Giveaways</a>
                    @endcan
                    @can('banners.view')
                    <a href="{{ route('admin.banners.index') }}" class="sidebar-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">Banners</a>
                    <a href="{{ route('admin.homepage-sections.index') }}" class="sidebar-link {{ request()->routeIs('admin.homepage-sections.*') ? 'active' : '' }}">Homepage Builder</a>
                    @endcan
                    @can('cms.view')
                    <a href="{{ route('admin.pages.index') }}" class="sidebar-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">CMS Pages</a>
                    <a href="{{ route('admin.faqs.index') }}" class="sidebar-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">FAQs</a>
                    @endcan

                    {{-- System --}}
                    @can('staff.view')
                    <div class="sidebar-section-title">System</div>
                    <a href="{{ route('admin.staff.index') }}" class="sidebar-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}">Staff</a>
                    @endcan
                    @can('roles.view')
                    <a href="{{ route('admin.roles.index') }}" class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">Roles & Permissions</a>
                    @endcan
                    @can('reports.view')
                    <a href="{{ route('admin.reports.sales') }}" class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">Reports</a>
                    @endcan
                    @can('settings.view')
                    <a href="{{ route('admin.settings.index') }}" class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">Store Settings</a>
                    <a href="{{ route('admin.database.info') }}" class="sidebar-link {{ request()->routeIs('admin.database.*') ? 'active' : '' }}">Database</a>
                    @endcan
                    @can('audit.view')
                    <a href="{{ route('admin.audit-logs.index') }}" class="sidebar-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}">Audit Logs</a>
                    @endcan
                    @can('store-locations.view')
                    <a href="{{ route('admin.store-locations.index') }}" class="sidebar-link {{ request()->routeIs('admin.store-locations.*') ? 'active' : '' }}">Store Locations</a>
                    @endcan
                    @can('shipping.view')
                    <a href="{{ route('admin.shipping.index') }}" class="sidebar-link {{ request()->routeIs('admin.shipping.*') ? 'active' : '' }}">Shipping</a>
                    @endcan
                </nav>

                {{-- Footer --}}
                <div class="p-3 border-t border-charcoal/5">
                    <a href="{{ route('home') }}" target="_blank" class="sidebar-link text-champagne-dark/80 hover:text-champagne-dark" title="View storefront">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span>View Storefront</span>
                    </a>
                    <a href="{{ route('admin.dashboard') }}#shortcuts" class="sidebar-link text-muted hover:text-charcoal-soft">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
                        <span>Keyboard Shortcuts</span>
                    </a>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <main class="flex-1 min-h-[calc(100vh-64px)] p-4 md:p-6 lg:p-8" id="main-content" role="main">
            {{-- Flash messages --}}
            @if (session('success'))
                <div class="alert alert-success mb-6" role="alert" x-data x-init="setTimeout(() => \$el.remove(), 5000)">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-success/60 hover:text-success" aria-label="Dismiss">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mb-6" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="flex-shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <div>
                        <p class="font-medium">Error</p>
                        <p class="text-sm mt-0.5">{{ session('error') }}</p>
                    </div>
                    <button @click="$el.parentElement.remove()" class="text-danger/60 hover:text-danger" aria-label="Dismiss">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
    <script src="{{ asset('js/image-compressor.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ClassicEditor === 'undefined') {
                console.warn('CKEditor 5 failed to load. Check the CDN and Content-Security-Policy.');
                return;
            }

            document.querySelectorAll('textarea.ckeditor').forEach(function (el) {
                if (el.dataset.ckeditorInitialized === 'true') {
                    return;
                }

                el.dataset.ckeditorInitialized = 'true';

                ClassicEditor.create(el).catch(function (error) {
                    console.error('CKEditor initialisation failed:', error);
                });
            });
        });
    </script>
</body>
</html>