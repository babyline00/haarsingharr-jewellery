@extends('layouts.storefront')

@section('title', 'Create Account — Haarsingharr')

@section('content')
    @php
        $authBanner = \App\Models\Banner::atLocation('auth')->published()->first();
        $authBgImage = $authBanner?->image ? \App\Support\Media::url($authBanner->image) : null;
    @endphp
    
<style>
    .haars-register {
        --gold: #b58a45;
        --gold-light: #e8d3a2;
        --ink: #28231e;
        position: relative;
        isolation: isolate;
        overflow: hidden;
        background: #faf7f1;
        color: var(--ink);
    }

    .haars-register * {
        box-sizing: border-box;
    }

    .haars-register .ambient {
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        filter: blur(2px);
        z-index: -1;
        animation: haars-float 9s ease-in-out infinite;
    }

    .haars-register .ambient-one {
        width: 360px;
        height: 360px;
        top: -150px;
        right: -90px;
        background: radial-gradient(circle, #ead8b7 0%, #ead8b700 70%);
    }

    .haars-register .ambient-two {
        width: 400px;
        height: 400px;
        bottom: -190px;
        left: -150px;
        background: radial-gradient(circle, #e8d5b8 0%, #e8d5b800 70%);
        animation-delay: -4s;
    }

    .haars-register .register-layout {
        display: grid;
        grid-template-columns: 1fr 0.88fr;
        max-width: 1080px;
        margin: 0 auto;
        min-height: 680px;
        border: 1px solid #e9dfd0;
        border-radius: 26px;
        overflow: hidden;
        background: #fffdf9;
        box-shadow: 0 24px 80px rgba(76, 55, 30, 0.10);
        animation: haars-enter .8s cubic-bezier(.2,.7,.2,1) both;
    }

    .haars-register .brand-panel {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 680px;
        padding: 44px;
        overflow: hidden;
        color: #fffaf0;
        @if ($authBgImage)
        background: url('{{ $authBgImage }}') center/cover no-repeat;
        @else
        background:
            radial-gradient(ellipse at 80% 20%, #a8865038, transparent 45%),
            linear-gradient(145deg, #29251f 0%, #403428 55%, #211d19 100%);
        @endif
    }

    .haars-register .brand-panel::before,
    .haars-register .brand-panel::after {
        content: "";
        position: absolute;
        border: 1px solid #d6b77936;
        border-radius: 50%;
        width: 390px;
        height: 390px;
        right: -170px;
        top: 110px;
        pointer-events: none;
        animation: haars-orbit 24s linear infinite;
    }

    .haars-register .brand-panel::after {
        width: 300px;
        height: 300px;
        right: -125px;
        top: 155px;
        animation-direction: reverse;
        animation-duration: 18s;
    }

    .haars-register .brand-content {
        position: relative;
        z-index: 1;
    }

    .haars-register .brand-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: #e7cf9c;
        font-size: 10px;
        letter-spacing: .23em;
        text-transform: uppercase;
    }

    .haars-register .gold-line {
        display: block;
        width: 42px;
        height: 1px;
        background: #d3b274;
    }

    .haars-register .brand-heading {
        margin: 76px 0 20px;
        max-width: 350px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(37px, 4vw, 53px);
        line-height: 1.13;
        font-weight: 400;
        letter-spacing: -.035em;
    }

    .haars-register .brand-heading em {
        color: #dfc28b;
        font-weight: 400;
    }

    .haars-register .brand-description {
        max-width: 320px;
        color: #d1c7b8;
        font-size: 13px;
        line-height: 1.9;
    }

    .haars-register .brand-footer {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 12px;
        padding-top: 25px;
        border-top: 1px solid #ffffff20;
        color: #e3d7c3;
        font-size: 11px;
        letter-spacing: .04em;
    }

    .haars-register .brand-star {
        color: #e2c48b;
        font-size: 19px;
    }

    .haars-register .form-panel {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 48px clamp(24px, 4.5vw, 62px);
    }

    .haars-register .welcome-label {
        margin-bottom: 12px;
        color: var(--gold);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .2em;
        text-transform: uppercase;
    }

    .haars-register .form-heading {
        margin: 0;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(31px, 3vw, 40px);
        font-weight: 400;
        line-height: 1.2;
        letter-spacing: -.035em;
    }

    .haars-register .form-subtitle {
        margin-top: 11px;
        margin-bottom: 32px;
        color: #898177;
        font-size: 12px;
        line-height: 1.8;
    }

    .haars-register .field {
        margin-bottom: 18px;
    }

    .haars-register .field-label {
        display: block;
        margin-bottom: 9px;
        color: #403a33;
        font-size: 12px;
        font-weight: 600;
    }

    .haars-register .input-wrap {
        position: relative;
    }

    .haars-register .field-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        width: 17px;
        height: 17px;
        transform: translateY(-50%);
        color: #a59a89;
        pointer-events: none;
        transition: color .25s ease;
    }

    .haars-register .register-input {
        display: block;
        width: 100%;
        min-height: 49px;
        padding: 13px 15px 13px 44px;
        border: 1px solid #e7e0d6;
        border-radius: 10px;
        outline: none;
        background: #fffefa;
        color: #302b25;
        font-family: inherit;
        font-size: 13px;
        transition: border-color .25s ease,
                    box-shadow .25s ease,
                    background .25s ease;
    }

    .haars-register .register-input::placeholder {
        color: #b5ada2;
    }

    .haars-register .register-input:hover {
        border-color: #cbb58f;
    }

    .haars-register .register-input:focus {
        border-color: var(--gold);
        background: #fff;
        box-shadow: 0 0 0 4px #b58a4519;
    }

    .haars-register .input-wrap:focus-within .field-icon {
        color: var(--gold);
    }

    .haars-register .password-input {
        padding-right: 48px;
    }

    .haars-register .toggle-password {
        position: absolute;
        top: 50%;
        right: 10px;
        display: grid;
        width: 32px;
        height: 32px;
        place-items: center;
        transform: translateY(-50%);
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: #918677;
        cursor: pointer;
        transition: background .2s, color .2s;
    }

    .haars-register .toggle-password:hover {
        background: #f2ebdf;
        color: #80602d;
    }

    .haars-register .toggle-password svg {
        width: 17px;
        height: 17px;
    }

    .haars-register .form-options {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin: 1px 0 25px;
    }

    .haars-register .remember-option {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #72695e;
        font-size: 11px;
        cursor: pointer;
    }

    .haars-register .remember-option input {
        width: 15px;
        height: 15px;
        accent-color: var(--gold);
        cursor: pointer;
    }

    .haars-register .gold-link {
        color: #967039;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        transition: color .2s;
    }

    .haars-register .gold-link:hover {
        color: #5d4120;
        text-decoration: underline;
        text-underline-offset: 4px;
    }

    .haars-register .submit-button {
        position: relative;
        display: flex;
        width: 100%;
        min-height: 51px;
        align-items: center;
        justify-content: center;
        gap: 10px;
        overflow: hidden;
        border: 1px solid #a98242;
        border-radius: 10px;
        background: linear-gradient(110deg, #967039, #c5a365 50%, #a27b3d);
        background-size: 200% 100%;
        color: #fffdf8;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .09em;
        cursor: pointer;
        box-shadow: 0 7px 18px #a9824228;
        transition: transform .25s ease, box-shadow .25s ease,
                    background-position .4s ease;
    }

    .haars-register .submit-button::before {
        content: "";
        position: absolute;
        inset: 0;
        transform: translateX(-130%) skewX(-20deg);
        background: linear-gradient(90deg, transparent, #ffffff3b, transparent);
        transition: transform .65s ease;
    }

    .haars-register .submit-button:hover {
        transform: translateY(-2px);
        background-position: 100% 0;
        box-shadow: 0 12px 25px #a9824240;
    }

    .haars-register .submit-button:hover::before {
        transform: translateX(130%) skewX(-20deg);
    }

    .haars-register .submit-button:active {
        transform: translateY(0);
    }

    .haars-register .submit-button span,
    .haars-register .submit-button svg {
        position: relative;
        z-index: 1;
    }

    .haars-register .submit-button svg {
        width: 16px;
        height: 16px;
        transition: transform .25s ease;
    }

    .haars-register .submit-button:hover svg {
        transform: translateX(4px);
    }

    .haars-register .status-message {
        margin-bottom: 22px;
        padding: 12px 14px;
        border: 1px solid #d9c7a1;
        border-radius: 9px;
        background: #f8f0e0;
        color: #75582d;
        font-size: 12px;
        line-height: 1.6;
        animation: haars-enter .4s ease both;
    }

    .haars-register .error-message {
        margin-top: 7px;
        color: #bb4141;
        font-size: 11px;
    }

    .haars-register .signup-prompt {
        margin-top: 27px;
        color: #8b8277;
        font-size: 11px;
        line-height: 1.8;
        text-align: center;
    }

    .haars-register .signup-prompt a {
        margin-left: 3px;
    }

    .haars-register .secure-note {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        margin-top: 24px;
        color: #a49a8d;
        font-size: 10px;
    }

    .haars-register .secure-note svg {
        width: 14px;
        height: 14px;
        color: #9e875e;
    }

    @keyframes haars-enter {
        from { opacity: 0; transform: translateY(18px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes haars-float {
        0%, 100% { transform: translate(0, 0); }
        50% { transform: translate(-18px, 22px); }
    }

    @keyframes haars-orbit {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @media (max-width: 767px) {
        .haars-register {
            padding: 28px 16px !important;
        }

        .haars-register .register-layout {
            grid-template-columns: 1fr;
            max-width: 440px;
            min-height: auto;
            border-radius: 20px;
        }

        .haars-register .brand-panel {
            min-height: auto;
            padding: 25px 25px 27px;
        }

        .haars-register .brand-heading {
            margin: 32px 0 12px;
            font-size: 35px;
            max-width: 290px;
        }

        .haars-register .brand-description {
            max-width: 300px;
            font-size: 12px;
        }

        .haars-register .brand-footer {
            margin-top: 26px;
            padding-top: 17px;
        }

        .haars-register .brand-panel::before {
            right: -250px;
            top: -60px;
        }

        .haars-register .brand-panel::after {
            right: -210px;
            top: -10px;
        }

        .haars-register .form-panel {
            padding: 32px 25px 30px;
        }

        .haars-register .form-subtitle {
            margin-bottom: 25px;
        }

        .haars-register .field {
            margin-bottom: 16px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .haars-register *,
        .haars-register *::before,
        .haars-register *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .01ms !important;
            scroll-behavior: auto !important;
        }
    }
</style>

<section class="haars-register py-12 md:py-20 px-4 md:px-6">
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <div class="register-layout">

        <!-- Luxury brand panel -->
        <div class="brand-panel" @if ($authBgImage) style="background: url('{{ $authBgImage }}') center/cover no-repeat;" @endif>
            @if ($authBgImage)
                <div style="position: absolute; inset: 0; background: linear-gradient(145deg, rgba(41, 37, 31, 0.85) 0%, rgba(64, 52, 40, 0.75) 55%, rgba(33, 29, 25, 0.85) 100%); z-index: 0;"></div>
            @endif
            <div class="brand-content" style="position: relative; z-index: 1;">
                <div class="brand-eyebrow">
                    <span class="gold-line"></span>
                    <span>HAARSINGHARR</span>
                    <span class="gold-line"></span>
                </div>

                <h2 class="brand-heading">
                    Join the
                    <em>Circle.</em>
                </h2>

                <p class="brand-description">
                    Become part of an exclusive community. Early access to
                    collections, bespoke invitations, and timeless beauty.
                </p>
            </div>

            <div class="brand-footer">
                <span class="brand-star">✳</span>
                <span>Start your Haarsingharr journey</span>
            </div>
        </div>

        <!-- Register form -->
        <div class="form-panel">
            <div class="welcome-label">Create your account</div>

            <h1 class="form-heading">Join Haarsingharr</h1>

            <p class="form-subtitle">
                Create an account to explore your favourites and
                enjoy a personalized shopping experience.
            </p>

            @if (session('status'))
                <div class="status-message" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <form action="{{ route('register.store') }}"
                  method="POST">
                @csrf

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div class="field" style="margin-bottom: 0;">
                        <label for="first_name" class="field-label">
                            First Name *
                        </label>

                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="1.6" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 22a8 8 0 0 1 16 0"/>
                            </svg>

                            <input
                                type="text"
                                id="first_name"
                                name="first_name"
                                class="register-input"
                                placeholder="First name"
                                value="{{ old('first_name') }}"
                                required
                                autofocus
                                autocomplete="given-name"
                                aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}"
                            >
                        </div>

                        @error('first_name')
                            <p class="error-message">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="field" style="margin-bottom: 0;">
                        <label for="last_name" class="field-label">
                            Last Name *
                        </label>

                        <div class="input-wrap">
                            <svg class="field-icon" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="1.6" aria-hidden="true">
                                <circle cx="12" cy="8" r="4"/>
                                <path d="M4 22a8 8 0 0 1 16 0"/>
                            </svg>

                            <input
                                type="text"
                                id="last_name"
                                name="last_name"
                                class="register-input"
                                placeholder="Last name"
                                value="{{ old('last_name') }}"
                                required
                                autocomplete="family-name"
                                aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}"
                            >
                        </div>

                        @error('last_name')
                            <p class="error-message">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Email -->
                <div class="field">
                    <label for="email" class="field-label">
                        Email Address *
                    </label>

                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.6" aria-hidden="true">
                            <rect x="3" y="5" width="18" height="14"
                                  rx="3"></rect>
                            <path d="m4 7 8 6 8-6"></path>
                        </svg>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="register-input"
                            placeholder="you@example.com"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('email') ? 'email-error' : '' }}"
                        >
                    </div>

                    @error('email')
                        <p class="error-message" id="email-error">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Phone -->
                <div class="field">
                    <label for="phone" class="field-label">
                        Phone *
                    </label>

                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.6" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>

                        <div class="flex gap-2">
                            <select name="phone_country" class="register-input w-24 flex-shrink-0" style="padding-left: 15px;">
                                <option value="+92">+92</option>
                                <option value="+971">+971</option>
                                <option value="+966">+966</option>
                                <option value="+1">+1</option>
                                <option value="+44">+44</option>
                            </select>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                class="register-input flex-1"
                                placeholder="300 1234567"
                                value="{{ old('phone') }}"
                                required
                                autocomplete="tel"
                                aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}"
                            >
                        </div>
                    </div>

                    @error('phone')
                        <p class="error-message">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Password -->
                <div class="field">
                    <label for="password" class="field-label">
                        Password *
                    </label>

                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.6" aria-hidden="true">
                            <rect x="4" y="10" width="16" height="11"
                                  rx="3"></rect>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                            <path d="M12 14v3"></path>
                        </svg>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="register-input password-input"
                            placeholder="Create a strong password"
                            required
                            autocomplete="new-password"
                            minlength="12"
                            aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                            aria-describedby="{{ $errors->has('password') ? 'password-error' : '' }}"
                        >

                        <button type="button"
                                class="toggle-password"
                                id="togglePassword"
                                aria-label="Show password"
                                aria-pressed="false">
                            <svg id="eyeIcon" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="1.7"
                                 stroke-linecap="round"
                                 stroke-linejoin="round"
                                 aria-hidden="true">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-xs text-muted mt-1">Min 12 characters with uppercase, lowercase, number and symbol.</p>

                    @error('password')
                        <p class="error-message" id="password-error">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="field">
                    <label for="password_confirmation" class="field-label">
                        Confirm Password *
                    </label>

                    <div class="input-wrap">
                        <svg class="field-icon" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.6" aria-hidden="true">
                            <rect x="4" y="10" width="16" height="11"
                                  rx="3"></rect>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                            <path d="M12 14v3"></path>
                        </svg>

                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="register-input password-input"
                            placeholder="Confirm your password"
                            required
                            autocomplete="new-password"
                            aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}"
                        >
                    </div>

                    @error('password_confirmation')
                        <p class="error-message">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Terms -->
                <div class="form-options" style="margin-bottom: 16px;">
                    <label class="remember-option" for="marketing_consent" style="cursor: pointer;">
                        <input type="checkbox"
                               id="marketing_consent"
                               name="marketing_consent"
                               value="1"
                               @checked(old('marketing_consent'))>
                        <span>Send me Haarsingharr collection updates</span>
                    </label>
                </div>

                <label class="remember-option" for="terms" style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 24px;">
                    <input type="checkbox"
                           id="terms"
                           name="terms"
                           value="1"
                           required
                           @checked(old('terms'))>
                    <span style="color: #72695e; font-size: 11px; line-height: 1.6;">
                        I accept the <a href="{{ route('pages.show', 'terms') }}" target="_blank" rel="noopener" class="gold-link">Terms</a> and
                        <a href="{{ route('pages.show', 'privacy-policy') }}" target="_blank" rel="noopener" class="gold-link">Privacy Policy</a>.
                    </span>
                </label>

                @error('terms')
                    <p class="error-message" style="margin-bottom: 16px;">
                        {{ $message }}
                    </p>
                @enderror

                <!-- Submit -->
                <button type="submit" class="submit-button">
                    <span>Create Account</span>

                    <svg viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-hidden="true">
                        <path d="M5 12h14"></path>
                        <path d="m13 6 6 6-6 6"></path>
                    </svg>
                </button>
            </form>

            <div class="signup-prompt">
                Already have an account?
                <a href="{{ route('login') }}" class="gold-link">
                    Sign in
                </a>
            </div>

            <div class="secure-note">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     aria-hidden="true">
                    <rect x="4" y="10" width="16" height="11" rx="3"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
                <span>Your account details are protected</span>
            </div>
        </div>
    </div>
</section>

<script>
    (() => {
        const button = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');

        if (!button || !password || !icon) return;

        const eyeOpen = `
            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
            <circle cx="12" cy="12" r="3"/>
        `;

        const eyeClosed = `
            <path d="m3 3 18 18"/>
            <path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a16 16 0 0 1-4 4.7"/>
            <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.3 0 2.5-.3 3.5-.8"/>
            <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>
        `;

        button.addEventListener('click', () => {
            const showing = password.type === 'password';

            password.type = showing ? 'text' : 'password';
            button.setAttribute('aria-label',
                showing ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(showing));
            icon.innerHTML = showing ? eyeClosed : eyeOpen;
        });
    })();
</script>

@endsection