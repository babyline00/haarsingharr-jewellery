@extends('layouts.storefront')

@section('title', 'Reset Password — Haarsingharr')

@section('content')
    <section class="py-16 md:py-24">
        <div class="container-haars max-w-md">
            <div class="text-center mb-8">
                <h1 class="font-serif text-3xl">Set a New Password</h1>
                <p class="text-sm text-muted mt-2">Choose a strong password — at least 12 characters.</p>
            </div>

            <div class="card p-6 md:p-8">
                <form action="{{ route('password.update') }}" method="post">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <input type="hidden" name="email" value="{{ $email ?? old('email') }}">

                    <div class="mb-4">
                        <label for="password">New password</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="12">
                        @error('password') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        @error('password_confirmation') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn-gold w-full">Reset Password</button>
                </form>
            </div>
        </div>
    </section>
@endsection
