<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $code }} — {{ $title }} · Haarsingharr</title>

    @if (! app()->environment('production'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('build/app.css') }}">
    @endif
</head>
<body class="min-h-screen bg-ivory-alt/30 text-charcoal flex items-center justify-center p-6">
    <main class="w-full max-w-md text-center">
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2" aria-label="Haarsingharr home">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-auto">
            <span class="font-serif text-xl tracking-tight">Haarsingharr</span>
        </a>

        <div class="card p-8 mt-6">
            <p class="font-serif text-6xl leading-none text-champagne-dark tabular-nums">{{ $code }}</p>
            <h1 class="font-serif text-2xl mt-3">{{ $title }}</h1>
            <p class="text-sm text-muted mt-2">{{ $message }}</p>

            <div class="flex flex-wrap justify-center gap-2 mt-6">
                <a href="{{ url('/') }}" class="btn-outline">Storefront</a>

                @auth
                    <a href="{{ route('admin.dashboard') }}" class="btn-gold">Dashboard</a>
                @endauth

                @guest
                    <a href="{{ route('login') }}" class="btn-gold">Sign in</a>
                @endguest
            </div>

            <p class="text-[11px] text-muted/70 mt-6">
                If this keeps happening, contact the site administrator.
            </p>
        </div>
    </main>
</body>
</html>
