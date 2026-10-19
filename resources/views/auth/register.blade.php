@extends('layouts.storefront')

@section('title', 'Create Account — Haarsingharr')

@section('content')
    <section class="py-16 md:py-24">
        <div class="container-haars max-w-md">
            <div class="text-center mb-8">
                <h1 class="font-serif text-3xl">Create Your Account</h1>
                <p class="text-sm text-muted mt-2">Join the Haarsingharr Circle for early access to collections.</p>
            </div>

            <div class="card p-6 md:p-8">
                <form action="{{ route('register.store') }}" method="post">
                    @csrf

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label for="first_name">First name</label>
                            <input type="text" id="first_name" name="first_name" required value="{{ old('first_name') }}" autocomplete="given-name">
                            @error('first_name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="last_name">Last name</label>
                            <input type="text" id="last_name" name="last_name" required value="{{ old('last_name') }}" autocomplete="family-name">
                            @error('last_name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required value="{{ old('email') }}" autocomplete="email">
                        @error('email') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="phone">Phone</label>
                        <div class="flex gap-2">
                            <select name="phone_country" class="w-24 text-sm">
                                <option value="+92">+92</option>
                                <option value="+971">+971</option>
                                <option value="+966">+966</option>
                                <option value="+1">+1</option>
                                <option value="+44">+44</option>
                            </select>
                            <input type="tel" id="phone" name="phone" required value="{{ old('phone') }}" autocomplete="tel" placeholder="300 1234567">
                        </div>
                        @error('phone') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="12">
                        <p class="text-xs text-muted mt-1">Min 12 characters with uppercase, lowercase, number and symbol.</p>
                        @error('password') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation">Confirm password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                        @error('password_confirmation') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-start gap-2 text-sm mb-3">
                        <input type="checkbox" name="marketing_consent" value="1">
                        <span class="text-charcoal-soft">Send me Haarsingharr collection updates and private invitations.</span>
                    </label>

                    <label class="flex items-start gap-2 text-sm mb-4">
                        <input type="checkbox" name="terms" value="1" required>
                        <span class="text-charcoal-soft">I accept the <a href="{{ route('pages.show', 'terms') }}" target="_blank" class="underline hover:text-champagne">Terms</a> and <a href="{{ route('pages.show', 'privacy-policy') }}" target="_blank" class="underline hover:text-champagne">Privacy Policy</a>.</span>
                    </label>
                    @error('terms') <p class="text-xs text-danger mb-3">{{ $message }}</p> @enderror

                    <button type="submit" class="btn-gold w-full">Create Account</button>
                </form>

                <div class="mt-6 text-center text-sm text-muted">
                    Already have an account? <a href="{{ route('login') }}" class="text-champagne-dark hover:underline">Sign in</a>
                </div>
            </div>
        </div>
    </section>
@endsection
