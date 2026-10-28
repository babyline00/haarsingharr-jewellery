@extends('admin.layout')

@section('title', 'Verify your sign in — Haarsingharr Admin')

@section('content')
    <div class="mx-auto max-w-lg space-y-6">
        <header>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-champagne-dark">Account security</p>
            <h1 class="mt-2 font-serif text-2xl md:text-3xl">Verify it’s you</h1>
            <p class="mt-2 text-sm text-muted">Enter a current code from your authenticator app, or use one unused recovery code.</p>
        </header>

        <section class="card p-5 md:p-7">
            <form action="{{ route('admin.mfa.verify') }}" method="post" class="space-y-5">
                @csrf
                <div>
                    <label for="code">Authenticator or recovery code</label>
                    <input id="code" name="code" type="text" inputmode="text" autocomplete="one-time-code" autocapitalize="characters" required autofocus class="font-mono text-lg tracking-wider" aria-describedby="code-help code-error">
                    <p id="code-help" class="mt-1 text-xs text-muted">Recovery codes can only be used once.</p>
                    @error('code')<p id="code-error" class="mt-2 text-sm text-danger" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-gold w-full">Continue to admin</button>
            </form>
        </section>

        <form action="{{ route('logout') }}" method="post">
            @csrf
            <button type="submit" class="text-sm text-muted underline underline-offset-4 hover:text-charcoal">Sign out</button>
        </form>
    </div>
@endsection
