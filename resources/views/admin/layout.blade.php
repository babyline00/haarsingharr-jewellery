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
<body class="min-h-screen bg-ivory-alt/30 text-charcoal"
      x-data="{ sidebar: false, shortcuts: false }"
      @keydown.window.escape="shortcuts = false">

    @php
        $adminUser = auth()->user();
        $unreadCount = $adminUser ? $adminUser->unreadNotifications()->count() : 0;
        $recentNotifications = $adminUser ? $adminUser->unreadNotifications()->take(10)->get() : collect();
        $roleName = $adminUser?->getRoleNames()->first();
    @endphp

    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[9999] btn-gold">
        Skip to main content
    </a>

    {{-- Top bar --}}
    <header class="bg-charcoal text-ivory sticky top-0 z-[var(--z-sticky)] border-b border-charcoal/10">
        <div class="flex items-center justify-between h-16 px-4 md:px-6">
            <div class="flex items-center gap-2 sm:gap-3">
                <button x-on:click="sidebar = !sidebar"
                        class="lg:hidden p-2 text-ivory/80 hover:text-ivory rounded-md hover:bg-charcoal/20 transition-colors"
                        aria-label="Toggle sidebar" :aria-expanded="sidebar.toString()">
                    <x-admin.icon name="menu" :size="22" />
                </button>

                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2" aria-label="Haarsingharr Admin Dashboard">
                    <img src="{{ \App\Support\Branding::logoUrl() }}" alt="" class="h-8 max-w-[132px] rounded bg-white/95 px-2 py-1 object-contain">
                    <span class="hidden lg:inline font-serif text-lg font-medium tracking-tight">Haarsingharr</span>
                </a>

                @if (! app()->environment('production'))
                    <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-widest bg-champagne/20 text-champagne"
                          title="Environment: {{ app()->environment() }}">
                        {{ app()->environment() }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-1.5 sm:gap-3 text-sm">
                {{-- Search / ⌘K command palette --}}
                <x-admin.command-palette />

                <a href="{{ route('home') }}" target="_blank" rel="noopener"
                   class="text-ivory/70 hover:text-champagne transition-colors hidden md:flex items-center gap-1.5 p-2 rounded-md hover:bg-charcoal/20"
                   title="View storefront">
                    <x-admin.icon name="external-link" :size="14" />
                    <span>View store</span>
                </a>

                {{-- Notifications --}}
                <div class="relative" x-data="{ open: false }">
                    <button x-on:click="open = !open"
                            class="p-2 text-ivory/80 hover:text-ivory rounded-md hover:bg-charcoal/20 transition-colors relative"
                            aria-label="Notifications ({{ $unreadCount }} unread)"
                            :aria-expanded="open.toString()">
                        <x-admin.icon name="bell" :size="20" />
                        <span x-show="{{ $unreadCount }} > 0"
                              class="absolute -top-1 -right-1 w-5 h-5 text-[10px] font-semibold bg-danger text-ivory rounded-full flex items-center justify-center">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    </button>

                    <div x-show="open" x-cloak x-on:click.outside="open = false" x-transition
                         class="absolute right-0 mt-2 w-80 bg-porcelain text-charcoal rounded-xl shadow-xl border border-charcoal/5 py-1 overflow-hidden">
                        <div class="px-4 py-3 border-b border-charcoal/5 flex items-center justify-between">
                            <h3 class="font-medium">Notifications</h3>
                            @if ($unreadCount > 0)
                                <form action="{{ route('admin.notifications.read-all') }}" method="post">
                                    @csrf
                                    <button type="submit" class="text-xs text-champagne-dark hover:underline">Mark all read</button>
                                </form>
                            @endif
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            @forelse ($recentNotifications as $notification)
                                <a href="{{ $notification->data['url'] ?? route('admin.notifications.index') }}"
                                   class="block px-4 py-3 hover:bg-ivory-alt/50 border-b border-charcoal/5 transition-colors">
                                    <p class="text-sm font-medium">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                    <p class="text-xs text-muted mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                                </a>
                            @empty
                                <p class="px-4 py-6 text-center text-xs text-muted">You're all caught up.</p>
                            @endforelse

                            <a href="{{ route('admin.notifications.index') }}"
                               class="block px-4 py-3 text-center text-xs text-champagne-dark hover:bg-ivory-alt transition-colors">
                                View all
                            </a>
                        </div>
                    </div>
                </div>

                {{-- User menu --}}
                <div class="relative" x-data="{ open: false }">
                    <button x-on:click="open = !open"
                            class="flex items-center gap-2.5 p-1.5 rounded-md text-ivory/80 hover:text-ivory hover:bg-charcoal/20 transition-colors"
                            aria-label="User menu" :aria-expanded="open.toString()">
                        <div class="w-8 h-8 rounded-full bg-champagne/30 flex items-center justify-center text-xs font-semibold">
                            {{ strtoupper(substr($adminUser->first_name ?? 'A', 0, 1) . substr($adminUser->last_name ?? '', 0, 1)) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-medium">{{ $adminUser->first_name ?? 'Admin' }} {{ $adminUser->last_name ?? '' }}</p>
                            <p class="text-[11px] text-ivory/50">{{ $roleName ?? 'Staff' }}</p>
                        </div>
                        <x-admin.icon name="chevron-down" :size="12" class="ml-1" />
                    </button>

                    <div x-show="open" x-cloak x-on:click.outside="open = false" x-transition
                         class="absolute right-0 mt-2 w-52 bg-porcelain text-charcoal rounded-xl shadow-xl border border-charcoal/5 py-1 overflow-hidden">
                        <div class="px-4 py-3 border-b border-charcoal/5">
                            <p class="text-sm font-medium">{{ $adminUser->first_name ?? 'Admin' }} {{ $adminUser->last_name ?? '' }}</p>
                            <p class="text-xs text-muted">{{ $adminUser->email ?? '' }}</p>
                        </div>

                        <a href="{{ route('account.profile.edit') }}" class="dropdown-item">Profile</a>
                        <a href="{{ route('account.profile.edit') }}#password" class="dropdown-item">Change Password</a>

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
        <div x-show="sidebar" x-cloak x-on:click="sidebar = false" x-transition.opacity
             class="lg:hidden fixed inset-0 top-16 bg-charcoal/40 z-[var(--z-fixed)]" aria-hidden="true"></div>

        {{-- Sidebar --}}
        <aside class="hidden lg:block bg-porcelain border-r border-charcoal/5 w-64 min-h-[calc(100vh-64px)] transition-transform duration-300 ease-out"
               :class="sidebar ? 'block! fixed z-[var(--z-fixed)] top-16 left-0 bottom-0 translate-x-0 overflow-y-auto' : '-translate-x-full lg:translate-x-0'">
            <div class="flex flex-col h-full">
                <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto" role="navigation" aria-label="Main navigation"
                     x-on:click="if ($event.target.closest('a')) sidebar = false">
                    {{-- Dashboard --}}
                    <a href="{{ route('admin.dashboard') }}"
                       class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                       @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                        <x-admin.icon name="dashboard" :size="18" />
                        <span>Dashboard</span>
                    </a>

                    {{-- Catalogue --}}
                    @can('products.view')
                        <x-admin.nav-group id="catalogue" label="Catalogue">
                            <a href="{{ route('admin.products.index') }}"
                               class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
                               @if (request()->routeIs('admin.products.*')) aria-current="page" @endif>
                                <x-admin.icon name="package" :size="18" />
                                <span>Products</span>
                            </a>
                            <a href="{{ route('admin.categories.index') }}"
                               class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                               @if (request()->routeIs('admin.categories.*')) aria-current="page" @endif>
                                <x-admin.icon name="grid" :size="18" />
                                <span>Categories</span>
                            </a>
                            <a href="{{ route('admin.collections.index') }}"
                               class="sidebar-link {{ request()->routeIs('admin.collections.*') ? 'active' : '' }}"
                               @if (request()->routeIs('admin.collections.*')) aria-current="page" @endif>
                                <x-admin.icon name="list" :size="18" />
                                <span>Collections</span>
                            </a>
                            <a href="{{ route('admin.inventory.index') }}"
                               class="sidebar-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}"
                               @if (request()->routeIs('admin.inventory.*')) aria-current="page" @endif>
                                <x-admin.icon name="database" :size="18" />
                                <span>Inventory</span>
                                <x-admin.nav-count :count="$navCounts['low_stock'] ?? 0" label="low stock variants" />
                            </a>
                        </x-admin.nav-group>
                    @endcan

                    {{-- Sales --}}
                    @canany(['orders.view', 'payments.view', 'refunds.view', 'exchanges.view'])
                        <x-admin.nav-group id="sales" label="Sales">
                            @can('orders.view')
                                <a href="{{ route('admin.orders.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.orders.*')) aria-current="page" @endif>
                                    <x-admin.icon name="shopping-bag" :size="18" />
                                    <span>Orders</span>
                                    <x-admin.nav-count :count="$navCounts['orders_pending'] ?? 0" label="orders in progress" />
                                </a>
                            @endcan

                            @can('payments.view')
                                <a href="{{ route('admin.payments.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.payments.*')) aria-current="page" @endif>
                                    <x-admin.icon name="credit-card" :size="18" />
                                    <span>Payments</span>
                                </a>
                            @endcan

                            @can('refunds.view')
                                <a href="{{ route('admin.refunds.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.refunds.*')) aria-current="page" @endif>
                                    <x-admin.icon name="refresh" :size="18" />
                                    <span>Refunds</span>
                                    <x-admin.nav-count :count="$navCounts['refunds_pending'] ?? 0" label="pending refunds" />
                                </a>
                            @endcan

                            @can('exchanges.view')
                                <a href="{{ route('admin.exchanges.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.exchanges.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.exchanges.*')) aria-current="page" @endif>
                                    <x-admin.icon name="truck" :size="18" />
                                    <span>Exchanges / Returns</span>
                                </a>
                            @endcan
                        </x-admin.nav-group>
                    @endcanany

                    {{-- Customers --}}
                    @canany(['customers.view', 'reviews.view'])
                        <x-admin.nav-group id="customers" label="Customers">
                            @can('customers.view')
                                <a href="{{ route('admin.customers.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.customers.*')) aria-current="page" @endif>
                                    <x-admin.icon name="users" :size="18" />
                                    <span>Customers</span>
                                </a>
                            @endcan

                            @canany(['staff.view', 'customers.view'])
                                <a href="{{ route('admin.users.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.users.*')) aria-current="page" @endif>
                                    <x-admin.icon name="user" :size="18" />
                                    <span>Users</span>
                                </a>
                            @endcanany

                            @can('reviews.view')
                                <a href="{{ route('admin.reviews.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.reviews.*')) aria-current="page" @endif>
                                    <x-admin.icon name="star" :size="18" />
                                    <span>Reviews</span>
                                    <x-admin.nav-count :count="$navCounts['reviews_pending'] ?? 0" label="reviews awaiting moderation" />
                                </a>
                            @endcan

                            @can('questions.view')
                                <a href="{{ route('admin.questions.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.questions.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.questions.*')) aria-current="page" @endif>
                                    <x-admin.icon name="inbox" :size="18" />
                                    <span>Product Questions</span>
                                </a>
                            @endcan
                        </x-admin.nav-group>
                    @endcanany

                    {{-- Marketing --}}
                    @canany(['coupons.view', 'banners.view', 'cms.view'])
                        <x-admin.nav-group id="marketing" label="Marketing & Content">
                            @can('coupons.view')
                                <a href="{{ route('admin.coupons.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.coupons.*')) aria-current="page" @endif>
                                    <x-admin.icon name="tag" :size="18" />
                                    <span>Coupons</span>
                                </a>
                            @endcan

                            @can('banners.view')
                                <a href="{{ route('admin.banners.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.banners.*')) aria-current="page" @endif>
                                    <x-admin.icon name="image" :size="18" />
                                    <span>Banners</span>
                                </a>
                                <a href="{{ route('admin.homepage-sections.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.homepage-sections.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.homepage-sections.*')) aria-current="page" @endif>
                                    <x-admin.icon name="layout" :size="18" />
                                    <span>Homepage Builder</span>
                                </a>
                            @endcan

                            @can('cms.view')
                                <a href="{{ route('admin.pages.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.pages.*')) aria-current="page" @endif>
                                    <x-admin.icon name="file-text" :size="18" />
                                    <span>CMS Pages</span>
                                </a>
                                <a href="{{ route('admin.faqs.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.faqs.*')) aria-current="page" @endif>
                                    <x-admin.icon name="info" :size="18" />
                                    <span>FAQs</span>
                                </a>
                            @endcan

                            @can('blog.view')
                                <a href="{{ route('admin.blog.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.blog.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.blog.*')) aria-current="page" @endif>
                                    <x-admin.icon name="edit-3" :size="18" />
                                    <span>Blog</span>
                                </a>
                            @endcan

                            @can('menus.view')
                                <a href="{{ route('admin.menus.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.menus.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.menus.*')) aria-current="page" @endif>
                                    <x-admin.icon name="list" :size="18" />
                                    <span>Navigation Menus</span>
                                </a>
                            @endcan

                            @can('email_templates.view')
                                <a href="{{ route('admin.emails.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.emails.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.emails.*')) aria-current="page" @endif>
                                    <x-admin.icon name="mail" :size="18" />
                                    <span>Email Templates</span>
                                </a>
                            @endcan
                        </x-admin.nav-group>
                    @endcanany

                    @canany(['contacts.view', 'newsletter.view'])
                        <x-admin.nav-group id="support" label="Support &amp; Comms">
                            @can('contacts.view')
                                <a href="{{ route('admin.enquiries.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.enquiries.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.enquiries.*')) aria-current="page" @endif>
                                    <x-admin.icon name="inbox" :size="18" />
                                    <span>Enquiries</span>
                                    <x-admin.nav-count :count="\App\Models\ContactEnquiry::where('status', 'new')->count()" label="new enquiries" />
                                </a>
                            @endcan

                            @can('newsletter.view')
                                <a href="{{ route('admin.newsletter.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.newsletter.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.newsletter.*')) aria-current="page" @endif>
                                    <x-admin.icon name="mail" :size="18" />
                                    <span>Newsletter</span>
                                </a>
                            @endcan
                        </x-admin.nav-group>
                    @endcanany

                    {{-- System --}}
                    @canany(['staff.view', 'roles.view', 'reports.view', 'settings.view', 'audit.view', 'store-locations.view', 'shipping.view'])
                        <x-admin.nav-group id="system" label="System">
                            @can('staff.view')
                                <a href="{{ route('admin.staff.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.staff.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.staff.*')) aria-current="page" @endif>
                                    <x-admin.icon name="shield" :size="18" />
                                    <span>Staff</span>
                                </a>
                            @endcan

                            @can('roles.view')
                                <a href="{{ route('admin.roles.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.roles.*')) aria-current="page" @endif>
                                    <x-admin.icon name="lock" :size="18" />
                                    <span>Roles &amp; Permissions</span>
                                </a>
                            @endcan

                            @can('reports.view')
                                <a href="{{ route('admin.reports.sales') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.reports.*')) aria-current="page" @endif>
                                    <x-admin.icon name="chart" :size="18" />
                                    <span>Reports</span>
                                </a>
                            @endcan

                            @can('settings.view')
                                <a href="{{ route('admin.settings.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.settings.*')) aria-current="page" @endif>
                                    <x-admin.icon name="settings" :size="18" />
                                    <span>Store Settings</span>
                                </a>
                                <a href="{{ route('admin.system.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.system.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.system.*')) aria-current="page" @endif>
                                    <x-admin.icon name="refresh" :size="18" />
                                    <span>System Health</span>
                                </a>
                                <a href="{{ route('admin.database.info') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.database.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.database.*')) aria-current="page" @endif>
                                    <x-admin.icon name="database" :size="18" />
                                    <span>Database</span>
                                </a>
                            @endcan

                            @can('audit.view')
                                <a href="{{ route('admin.audit-logs.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.audit-logs.*')) aria-current="page" @endif>
                                    <x-admin.icon name="list" :size="18" />
                                    <span>Audit Logs</span>
                                </a>
                                <a href="{{ route('admin.security-events.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.security-events.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.security-events.*')) aria-current="page" @endif>
                                    <x-admin.icon name="alert-triangle" :size="18" />
                                    <span>Security Events</span>
                                </a>
                                <a href="{{ route('admin.active-sessions.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.active-sessions.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.active-sessions.*')) aria-current="page" @endif>
                                    <x-admin.icon name="clock" :size="18" />
                                    <span>Active Sessions</span>
                                </a>
                            @endcan

                            @can('store-locations.view')
                                <a href="{{ route('admin.store-locations.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.store-locations.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.store-locations.*')) aria-current="page" @endif>
                                    <x-admin.icon name="home" :size="18" />
                                    <span>Store Locations</span>
                                </a>
                            @endcan

                            @can('shipping.view')
                                <a href="{{ route('admin.shipping.index') }}"
                                   class="sidebar-link {{ request()->routeIs('admin.shipping.*') ? 'active' : '' }}"
                                   @if (request()->routeIs('admin.shipping.*')) aria-current="page" @endif>
                                    <x-admin.icon name="truck" :size="18" />
                                    <span>Shipping</span>
                                </a>
                            @endcan
                        </x-admin.nav-group>
                    @endcanany
                </nav>

                {{-- Footer --}}
                <div class="p-3 border-t border-charcoal/5">
                    <a href="{{ route('home') }}" target="_blank" rel="noopener"
                       class="sidebar-link text-champagne-dark/80 hover:text-champagne-dark" title="View storefront">
                        <x-admin.icon name="external-link" :size="18" />
                        <span>View Storefront</span>
                    </a>

                    <button type="button" x-on:click="shortcuts = true"
                            class="sidebar-link w-full text-left text-muted hover:text-charcoal-soft">
                        <x-admin.icon name="info" :size="18" />
                        <span>Keyboard Shortcuts</span>
                    </button>

                    <p class="px-3 pt-2 text-[10px] text-muted/70">Haarsingharr Admin</p>
                </div>
            </div>
        </aside>

        {{-- Main content --}}
        <main class="flex-1 min-w-0 min-h-[calc(100vh-64px)] p-4 md:p-6 lg:p-8" id="main-content" role="main" tabindex="-1">
            <x-admin.breadcrumbs />

            {{-- Flash messages --}}
            @if (session('success'))
                <div class="alert alert-success mb-6" role="status" x-data x-init="setTimeout(() => $el.remove(), 5000)">
                    <x-admin.icon name="check-circle" :size="20" class="mt-0.5" />
                    <div class="min-w-0">
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                    <button x-on:click="$el.parentElement.remove()" class="ml-auto text-success/60 hover:text-success" aria-label="Dismiss">
                        <x-admin.icon name="x" :size="16" />
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger mb-6" role="alert">
                    <x-admin.icon name="alert-circle" :size="20" class="mt-0.5" />
                    <div class="min-w-0">
                        <p class="font-medium">Error</p>
                        <p class="text-sm mt-0.5">{{ session('error') }}</p>
                    </div>
                    <button x-on:click="$el.parentElement.remove()" class="ml-auto text-danger/60 hover:text-danger" aria-label="Dismiss">
                        <x-admin.icon name="x" :size="16" />
                    </button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Keyboard shortcuts --}}
    <div x-show="shortcuts" x-cloak class="modal" role="dialog" aria-modal="true" aria-labelledby="shortcuts-title">
        <div class="modal-backdrop" x-on:click="shortcuts = false" aria-hidden="true"></div>

        <div class="modal-content max-w-md p-5 relative" x-transition>
            <div class="flex items-center justify-between mb-4">
                <h2 id="shortcuts-title" class="font-serif text-lg">Keyboard shortcuts</h2>
                <button type="button" x-on:click="shortcuts = false"
                        class="p-1 rounded-md hover:bg-ivory-alt transition-colors" aria-label="Close">
                    <x-admin.icon name="x" :size="16" />
                </button>
            </div>

            <dl class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-charcoal-soft">Search admin</dt>
                    <dd class="flex gap-1">
                        <kbd class="px-1.5 py-0.5 rounded border border-charcoal/10 bg-ivory-alt text-xs">⌘K</kbd>
                        <kbd class="px-1.5 py-0.5 rounded border border-charcoal/10 bg-ivory-alt text-xs">Ctrl K</kbd>
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-charcoal-soft">Close dialog or menu</dt>
                    <dd><kbd class="px-1.5 py-0.5 rounded border border-charcoal/10 bg-ivory-alt text-xs">Esc</kbd></dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-charcoal-soft">Toggle mobile sidebar</dt>
                    <dd><kbd class="px-1.5 py-0.5 rounded border border-charcoal/10 bg-ivory-alt text-xs">Menu</kbd></dd>
                </div>
            </dl>
        </div>
    </div>

    @stack('scripts')
    <script src="{{ asset('js/image-compressor.js') }}"></script>
</body>
</html>
