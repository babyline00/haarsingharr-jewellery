@extends('admin.layout')

@section('title', 'Secure your account — Haarsingharr Admin')

@section('content')
    <div class="mx-auto max-w-2xl space-y-6">
        <header>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-champagne-dark">Account security</p>
            <h1 class="mt-2 font-serif text-2xl md:text-3xl">Set up two-step verification</h1>
            <p class="mt-2 text-sm text-muted">Your administrator account needs an authenticator app. You’ll use a fresh six-digit code each time you sign in.</p>
        </header>

        <section class="card space-y-5 p-5 md:p-7" aria-labelledby="mfa-setup-title">
            <h2 id="mfa-setup-title" class="font-serif text-xl">1. Add this account to your authenticator</h2>
            <p class="text-sm text-charcoal-soft">In Google Authenticator, Microsoft Authenticator, 1Password, or another TOTP app, add an account manually with this key.</p>
            <div class="rounded-lg border border-charcoal/10 bg-ivory-alt/50 p-4">
                <p class="text-xs uppercase tracking-widest text-muted">Setup key</p>
                <p class="mt-2 break-all font-mono text-lg font-semibold tracking-wider" id="mfa-secret">{{ implode(' ', str_split($secret, 4)) }}</p>
                <p class="mt-2 text-xs text-muted">Account: {{ auth()->user()->email }} · Time-based · 6 digits · 30 seconds</p>
            </div>

            <h2 class="pt-2 font-serif text-xl">2. Confirm the app is connected</h2>
            <p class="text-sm text-charcoal-soft">Enter the current six-digit code shown in the app. The key is saved only after verification.</p>
            <form action="{{ route('admin.mfa.confirm') }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label for="code">Authenticator code</label>
                    <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus class="max-w-xs text-center font-mono text-xl tracking-[0.35em]" aria-describedby="code-error">
                    @error('code')<p id="code-error" class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn-gold">Verify and enable</button>
            </form>
        </section>

        <form action="{{ route('logout') }}" method="post">
            @csrf
            <button type="submit" class="text-sm text-muted underline underline-offset-4 hover:text-charcoal">Sign out</button>
        </form>
    </div>
@endsection
