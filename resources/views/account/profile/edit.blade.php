@extends('layouts.storefront')

@section('title', 'Profile — Haarsingharr')

@section('content')
    <section class="py-12">
        <div class="container-haars max-w-2xl">
            <h1 class="font-serif text-3xl mb-8">Your Profile</h1>

            <div class="card p-6 md:p-8">
                <form action="{{ route('account.profile.update') }}" method="post">
                    @csrf
                    @method('PATCH')

                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="first_name">First name</label>
                            <input type="text" id="first_name" name="first_name" required value="{{ old('first_name', $user->first_name) }}">
                            @error('first_name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="last_name">Last name</label>
                            <input type="text" id="last_name" name="last_name" required value="{{ old('last_name', $user->last_name) }}">
                            @error('last_name') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="email">Email</label>
                        <input type="email" id="email" value="{{ $user->email }}" disabled>
                        <p class="text-xs text-muted mt-1">Contact support to change your email.</p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="phone_country">Phone country</label>
                            <select id="phone_country" name="phone_country">
                                <option value="+92" @selected($user->phone_country === '+92')>+92</option>
                                <option value="+971">+971</option>
                                <option value="+966">+966</option>
                            </select>
                        </div>
                        <div>
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required value="{{ old('phone', $user->phone) }}">
                            @error('phone') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn-gold">Save Changes</button>
                </form>
            </div>

            <div class="card p-6 md:p-8 mt-6">
                <h2 class="font-serif text-xl mb-4">Security</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between py-2 border-b border-charcoal/5">
                        <span class="text-charcoal-soft">Password last changed</span>
                        <span class="text-muted">{{ $user->password_changed_at?->format('j F Y') ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-charcoal/5">
                        <span class="text-charcoal-soft">Last login</span>
                        <span class="text-muted">{{ $user->last_login_at?->format('j F Y, g:i A') ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-charcoal-soft">Two-factor authentication</span>
                        @if ($user->hasMfaEnabled())
                            <span class="badge bg-success/10 text-success">Enabled</span>
                        @else
                            <span class="badge bg-warning/10 text-warning">Not enrolled</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card p-6 md:p-8 mt-6" id="password">
                <h2 class="font-serif text-xl mb-4">Change Password</h2>
                <form action="{{ route('account.password.update') }}" method="post" class="space-y-4">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                        @error('current_password') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="password" required autocomplete="new-password">
                        <p class="text-xs text-muted mt-1">At least 12 characters with uppercase, lowercase, a digit and a symbol.</p>
                        @error('password') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-gold">Update Password</button>
                </form>
            </div>
        </div>
    </section>
@endsection
