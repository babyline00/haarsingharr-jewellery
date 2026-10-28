@extends('layouts.storefront')

@section('title', 'Verify Your Email — Haarsingharr')

@section('content')
    @php
        $authBanner = \App\Models\Banner::atLocation('auth')->published()->first();
        $authBgImage = $authBanner?->image ? \App\Support\Media::url($authBanner->image) : null;
    @endphp
    
<style>
    .haars-verify {
        --gold: #b58a45;
        --ink: #28231e;
        position: relative;
        isolation: isolate;
        overflow: hidden;
        background: #faf7f1;
        color: var(--ink);
    }

    .haars-verify * {
        box-sizing: border-box;
    }

    .haars-verify .ambient {
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        filter: blur(2px);
        z-index: -1;
        animation: haars-float 9s ease-in-out infinite;
    }

    .haars-verify .ambient-one {
        width: 360px;
        height: 360px;
        top: -150px;
        right: -90px;
        background: radial-gradient(circle, #ead8b7 0%, #ead8b700 70%);
    }

    .haars-verify .ambient-two {
        width: 400px;
        height: 400px;
        bottom: -190px;
        left: -150px;
        background: radial-gradient(circle, #e8d5b8 0%, #e8d5b800 70%);
        animation-delay: -4s;
    }

    .haars-verify .verify-layout {
        display: grid;
        grid-template-columns: 1fr 0.88fr;
        max-width: 1080px;
        margin: 0 auto;
        min-height: 590px;
        border: 1px solid #e9dfd0;
        border-radius: 26px;
        overflow: hidden;
        background: #fffdf9;
        box-shadow: 0 24px 80px rgba(76, 55, 30, 0.10);
        animation: haars-enter .8s cubic-bezier(.2,.7,.2,1) both;
    }

    .haars-verify .brand-panel {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 590px;
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

    .haars-verify .brand-panel::before,
    .haars-verify .brand-panel::after {
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

    .haars-verify .brand-panel::after {
        width: 300px;
        height: 300px;
        right: -125px;
        top: 155px;
        animation-direction: reverse;
        animation-duration: 18s;
    }

    .haars-verify .brand-content {
        position: relative;
        z-index: 1;
    }

    .haars-verify .brand-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        color: #e7cf9c;
        font-size: 10px;
        letter-spacing: .23em;
        text-transform: uppercase;
    }

    .haars-verify .gold-line {
        display: block;
        width: 42px;
        height: 1px;
        background: #d3b274;
    }

    .haars-verify .brand-heading {
        margin: 76px 0 20px;
        max-width: 350px;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(37px, 4vw, 53px);
        line-height: 1.13;
        font-weight: 400;
        letter-spacing: -.035em;
    }

    .haars-verify .brand-heading em {
        color: #dfc28b;
        font-weight: 400;
    }

    .haars-verify .brand-description {
        max-width: 320px;
        color: #d1c7b8;
        font-size: 13px;
        line-height: 1.9;
    }

    .haars-verify .brand-footer {
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

    .haars-verify .brand-star {
        color: #e2c48b;
        font-size: 19px;
    }

    .haars-verify .form-panel {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 48px clamp(24px, 4.5vw, 62px);
    }

    .haars-verify .welcome-label {
        margin-bottom: 12px;
        color: var(--gold);
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .2em;
        text-transform: uppercase;
    }

    .haars-verify .form-heading {
        margin: 0;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(31px, 3vw, 40px);
        font-weight: 400;
        line-height: 1.2;
        letter-spacing: -.035em;
    }

    .haars-verify .form-subtitle {
        margin-top: 11px;
        margin-bottom: 32px;
        color: #898177;
        font-size: 12px;
        line-height: 1.8;
    }

    .haars-verify .verify-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 24px;
        border-radius: 50%;
        background: linear-gradient(135deg, #f8f0e0, #e8d5b8);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--gold);
    }

    .haars-verify .verify-icon svg {
        width: 32px;
        height: 32px;
    }

    .haars-verify .submit-button {
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

    .haars-verify .submit-button::before {
        content: "";
        position: absolute;
        inset: 0;
        transform: translateX(-130%) skewX(-20deg);
        background: linear-gradient(90deg, transparent, #ffffff3b, transparent);
        transition: transform .65s ease;
    }

    .haars-verify .submit-button:hover {
        transform: translateY(-2px);
        background-position: 100% 0;
        box-shadow: 0 12px 25px #a9824240;
    }

    .haars-verify .submit-button:hover::before {
        transform: translateX(130%) skewX(-20deg);
    }

    .haars-verify .submit-button:active {
        transform: translateY(0);
    }

    .haars-verify .submit-button span,
    .haars-verify .submit-button svg {
        position: relative;
        z-index: 1;
    }

    .haars-verify .status-message {
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

    .haars-verify .gold-link {
        color: #967039;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        transition: color .2s;
    }

    .haars-verify .gold-link:hover {
        color: #5d4120;
        text-decoration: underline;
        text-underline-offset: 4px;
    }

    .haars-verify .signup-prompt {
        margin-top: 27px;
        color: #8b8277;
        font-size: 11px;
        line-height: 1.8;
        text-align: center;
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
        .haars-verify {
            padding: 28px 16px !important;
        }

        .haars-verify .verify-layout {
            grid-template-columns: 1fr;
            max-width: 440px;
            min-height: auto;
            border-radius: 20px;
        }

        .haars-verify .brand-panel {
            min-height: auto;
            padding: 25px 25px 27px;
        }

        .haars-verify .brand-heading {
            margin: 32px 0 12px;
            font-size: 35px;
            max-width: 290px;
        }

        .haars-verify .brand-description {
            max-width: 300px;
            font-size: 12px;
        }

        .haars-verify .brand-footer {
            margin-top: 26px;
            padding-top: 17px;
        }

        .haars-verify .brand-panel::before {
            right: -250px;
            top: -60px;
        }

        .haars-verify .brand-panel::after {
            right: -210px;
            top: -10px;
        }

        .haars-verify .form-panel {
            padding: 32px 25px 30px;
        }

        .haars-verify .form-subtitle {
            margin-bottom: 25px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .haars-verify *,
        .haars-verify *::before,
        .haars-verify *::after {
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: .01ms !important;
            scroll-behavior: auto !important;
        }
    }
</style>

<section class="haars-verify py-12 md:py-20 px-4 md:px-6">
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <div class="verify-layout">

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
                    Verify your
                    <em>email.</em>
                </h2>

                <p class="brand-description">
                    We've sent a verification link to your email.
                    Please check your inbox to activate your account.
                </p>
            </div>

            <div class="brand-footer">
                <span class="brand-star">✳</span>
                <span>Secure account verification</span>
            </div>
        </div>

        <!-- Verify form -->
        <div class="form-panel">
            <div class="welcome-label">Email verification</div>

            <h1 class="form-heading">Verify Your Email</h1>

            <p class="form-subtitle">
                We've sent a verification link to <strong>{{ auth()->user()->email ?? '' }}</strong>.
                Please click the link in the email to activate your account.
            </p>

            @if (session('status'))
                <div class="status-message" role="status">
                    {{ session('status') }}
                </div>
            @endif

            <div class="verify-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 6l-10 7L2 6"/>
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </div>

            <form action="{{ route('verification.send') }}" method="post">
                @csrf
                <button type="submit" class="submit-button">
                    <span>Resend Verification Email</span>

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

            <form action="{{ route('logout') }}" method="post" class="mt-4">
                @csrf
                <button type="submit" class="btn-ghost w-full text-sm">Sign out</button>
            </form>

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

@endsection