<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FBF8F2">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name') . ' — Jewelry For Every You')</title>
    <meta name="description" content="@yield('meta_description', 'Haarsingharr.pk — premium jewellery crafted for the modern Pakistani bride and connoisseur. Jewelry • For Every You.')">
    <meta name="keywords" content="@yield('meta_keywords', 'jewellery, gold, bridal, rings, necklaces, earrings, bracelets, Pakistan, Haarsingharr')">
    <meta name="author" content="Haarsingharr.pk">
    <meta name="application-name" content="Haarsingharr.pk">

    @stack('meta')

    @if (config('app.force_https'))
        <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    @endif

    {{-- Open Graph --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', config('app.name') . ' — Jewelry For Every You.')">
    <meta property="og:url" content="{{ request()->fullUrl() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="{{ config('app.name') }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', config('app.name'))">
    <meta name="twitter:description" content="@yield('og_description', config('app.name'))">
    <meta name="twitter:image" content="@yield('og_image', asset('images/og-default.jpg'))">

    {{-- Canonical --}}
    <link rel="canonical" href="@yield('canonical', request()->fullUrl())">

    {{-- Structured data --}}
    @yield('structured_data')

    {{-- Favicons & manifest --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/favicon-96x96.png') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="msapplication-TileColor" content="#C8A968">
    <meta name="msapplication-TileImage" content="{{ asset('images/mstile-150x150.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @if (! app()->environment('production'))
        <link rel="preconnect" href="http://localhost:5173">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="min-h-screen flex flex-col bg-ivory text-charcoal">

    {{-- Skip link for screen readers --}}
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-charcoal focus:text-ivory focus:px-3 focus:py-2 focus:rounded">
        Skip to main content
    </a>

    {{-- Announcement bar (database-driven) --}}
    @if (isset($announcement))
        <div class="bg-charcoal text-ivory text-center text-xs md:text-sm py-2 px-4" x-data="{ visible: true }" x-show="visible">
            <div class="container-haars flex items-center justify-center gap-3">
                <span class="font-medium tracking-wide">{{ $announcement->heading ?? 'Complimentary shipping & free returns within 7 days' }}</span>
                @if ($link = $announcement->link_url ?? null)
                    <a href="{{ route('banners.click', $announcement) }}" class="underline underline-offset-2 hover:text-champagne transition-colors">{{ $announcement->link_text ?? $announcement->heading ?? 'Details' }}</a>
                @endif
                <button @click="visible = false" class="ml-2 text-ivory/60 hover:text-ivory" aria-label="Dismiss announcement">×</button>
            </div>
        </div>
    @endif

    {{-- Staff impersonation banner (never renders for real customers) --}}
    @if (session('impersonator_id') && auth()->check())
        <div class="bg-champagne text-charcoal text-center text-xs md:text-sm py-2 px-4" role="status">
            <div class="container-haars flex items-center justify-center gap-3">
                <span class="font-medium">
                    Viewing the store as
                    <span class="font-semibold">{{ auth()->user()->name ?: auth()->user()->email }}</span>
                    — staff session is saved.
                </span>
                <form action="{{ route('impersonation.exit') }}" method="post">
                    @csrf
                    <button type="submit" class="underline underline-offset-2 font-semibold hover:text-charcoal/80">Return to admin</button>
                </form>
            </div>
        </div>
    @endif

    {{-- Homepage preview banner (staff only, ?preview=1) --}}
    @if (! empty($isPreview))
        <div class="bg-charcoal text-champagne text-center text-xs md:text-sm py-2 px-4" role="status">
            <div class="container-haars flex items-center justify-center gap-3">
                <span class="font-medium tracking-wide">Homepage preview — drafts and sections outside their schedule are included.</span>
            </div>
        </div>
    @endif

    {{-- Header --}}
    @include('layouts.partials.header')

    {{-- Breadcrumbs (optional) --}}
    @hasSection('breadcrumbs')
        <div class="bg-ivory-alt/40 border-b border-charcoal/5">
            <div class="container-haars py-2 text-xs text-muted">
                @yield('breadcrumbs')
            </div>
        </div>
    @endif

    {{-- Main content --}}
    <main id="main-content" class="flex-1">
        @if (session('success'))
            <div class="container-haars mt-4" x-data x-init="setTimeout(() => $el.remove(), 6000)">
                <div class="animate-toast-in bg-success/10 border border-success/30 text-success px-4 py-3 rounded-md text-sm" role="status">
                    {{ session('success') }}
                </div>
            </div>
        @endif
        @if (session('error'))
            <div class="container-haars mt-4">
                <div class="animate-toast-in bg-danger/10 border border-danger/30 text-danger px-4 py-3 rounded-md text-sm" role="alert">
                    {{ session('error') }}
                </div>
            </div>
        @endif
        @if (session('status'))
            <div class="container-haars mt-4">
                <div class="animate-toast-in bg-champagne/10 border border-champagne/30 text-champagne-dark px-4 py-3 rounded-md text-sm" role="status">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        @yield('content')
    </main>

    {{-- Footer --}}
    @include('layouts.partials.footer')

    {{-- WhatsApp click-to-chat (only when configured) --}}
    @if (config('storefront.notifications.whatsapp_number') && config('app.features.whatsapp_click_to_chat'))
        <a href="https://wa.me/{{ preg_replace('/\D/', '', config('storefront.notifications.whatsapp_number')) }}?text=Hello%20Haarsingharr" target="_blank" rel="noopener" class="fixed bottom-5 right-5 z-40 bg-[#25D366] text-white rounded-full w-14 h-14 flex items-center justify-center shadow-lg hover:scale-105 transition-transform" aria-label="Chat on WhatsApp">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9s-.5-.1-.7.1-.8.9-1 1.1-.4.2-.6.1c-.8-.4-1.7-.8-2.6-2-.7-.7-1.1-1.6-1.2-1.8-.1-.2 0-.4.1-.5.1-.1.2-.3.4-.5s.2-.3.3-.5c.1-.1 0-.3 0-.4s-.7-1.7-.9-2.3c-.2-.5-.5-.5-.7-.5h-.6c-.2 0-.5.1-.7.4C3.8 7.8 3 8.6 3 10.2c0 1.7 1.2 3.3 1.4 3.5.1.2 2.4 3.6 5.7 5 2.9 1.1 3.4.9 4 .8.6-.1 1.7-.7 2-1.4.2-.7.2-1.2.2-1.4-.1-.1-.3-.2-.6-.3z"/>
                <path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.5A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-2.9.9.9-2.8-.2-.3A8 8 0 1 1 12 20z"/>
            </svg>
        </a>
    @endif

    {{-- Back to top --}}
    <button
        type="button"
        x-data="{ visible: false }"
        x-init="const onScroll = () => { visible = window.scrollY > 600 }; window.addEventListener('scroll', onScroll, { passive: true }); onScroll()"
        x-show="visible"
        x-cloak
        x-transition.opacity.duration.200ms
        @click="const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches; window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' })"
        class="fixed bottom-5 left-5 z-40 flex h-11 w-11 items-center justify-center rounded-full
               border border-charcoal/10 bg-porcelain/95 text-charcoal-soft shadow-md
               transition-colors hover:bg-ivory-alt hover:text-charcoal"
        aria-label="Back to top"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/>
        </svg>
    </button>

    @stack('scripts')
</body>
</html>
