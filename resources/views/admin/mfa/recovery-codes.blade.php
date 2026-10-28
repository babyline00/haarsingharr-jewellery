@extends('admin.layout')

@section('title', 'Save recovery codes — Haarsingharr Admin')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <header>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-champagne-dark">Account security</p>
            <h1 class="mt-2 font-serif text-2xl md:text-3xl">Save your recovery codes</h1>
            <p class="mt-2 text-sm text-muted">Keep these somewhere private and separate from your device. Each code works once if you can’t access your authenticator.</p>
        </header>

        <section class="card p-5 md:p-7" aria-label="One-time recovery codes">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 md:grid-cols-3">
                @foreach ($recoveryCodes as $code)
                    <code class="rounded-md bg-ivory-alt px-3 py-2 text-center font-mono text-sm">{{ $code }}</code>
                @endforeach
            </div>
            <p class="mt-5 text-xs text-muted">These codes are shown only once. They are stored in encrypted form and cannot be viewed again.</p>
        </section>

        <a href="{{ route('admin.dashboard') }}" class="btn-gold">Continue to dashboard</a>
    </div>
@endsection
