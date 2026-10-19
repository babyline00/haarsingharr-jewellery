@extends('layouts.storefront')

@section('title', 'Sign In — Haarsingharr')

@section('content')
    <section class="py-16 md:py-24">
        <div class="container-haars max-w-md">
            <div class="text-center mb-8">
                <h1 class="font-serif text-3xl">Welcome Back</h1>
                <p class="text-sm text-muted mt-2">Sign in to your Haarsingharr account</p>
            </div>

            <div class="card p-6 md:p-8">
                @if (session('status'))
                    <div class="bg-champagne/10 border border-champagne/30 text-champagne-dark px-4 py-3 rounded-md text-sm mb-4">{{ session('status') }}</div>
                @endif

                <form action="{{ route('login.attempt') }}" method="post">
                    @csrf

                    <div class="mb-4">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}" autofocus autocomplete="email">
                        @error('email') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center justify-between">
                            <label for="password">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs text-champagne-dark hover:underline">Forgot?</a>
                        </div>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        @error('password') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm mb-4">
                        <input type="checkbox" name="remember" value="1">
                        <span class="text-charcoal-soft">Keep me signed in</span>
                    </label>

                    <button type="submit" class="btn-gold w-full">Sign In</button>
                </form>

                <div class="mt-6 text-center text-sm text-muted">
                    New to Haarsingharr? <a href="{{ route('register') }}" class="text-champagne-dark hover:underline">Create an account</a>
                </div>
            </div>
        </div>
    </section>
@endsection
