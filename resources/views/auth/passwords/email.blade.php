@extends('layouts.storefront')

@section('title', 'Reset Password — Haarsingharr')

@section('content')
    <section class="py-16 md:py-24">
        <div class="container-haars max-w-md">
            <div class="text-center mb-8">
                <h1 class="font-serif text-3xl">Reset Password</h1>
                <p class="text-sm text-muted mt-2">Enter your email to receive a reset link.</p>
            </div>

            <div class="card p-6 md:p-8">
                @if (session('status'))
                    <div class="bg-success/10 border border-success/30 text-success px-4 py-3 rounded-md text-sm mb-4">{{ session('status') }}</div>
                @endif

                <form action="{{ route('password.email') }}" method="post">
                    @csrf
                    <div class="mb-4">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}" autofocus>
                        @error('email') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn-gold w-full">Send Reset Link</button>
                </form>

                <div class="mt-6 text-center text-sm">
                    <a href="{{ route('login') }}" class="text-champagne-dark hover:underline">Back to sign in</a>
                </div>
            </div>
        </div>
    </section>
@endsection
