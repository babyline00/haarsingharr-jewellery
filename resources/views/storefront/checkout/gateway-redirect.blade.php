<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Redirecting to {{ $gateway_url ?? 'payment gateway' }}…</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, sans-serif; background: #FBF8F2; color: #1F1B16; text-align: center; padding: 80px 20px; }
        h1 { font-family: Georgia, serif; font-weight: 500; margin: 0 0 8px; }
        p { color: #6F6A63; font-size: 14px; margin: 0 0 32px; }
        .spinner { display: inline-block; width: 36px; height: 36px; border: 3px solid #C8A968; border-top-color: transparent; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 16px; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hidden-form { display: none; }
    </style>
</head>
<body>
    <div class="spinner"></div>
    <h1>Redirecting to {{ $gateway_url ?? 'the payment gateway' }}</h1>
    <p>Please wait — do not close this window. You'll be returned to Haarsingharr after payment.</p>

    {{-- Auto-submit form to the gateway --}}
    <form id="payment-form" method="{{ $method ?? 'POST' }}" action="{{ $gateway_url ?? '' }}" class="hidden-form">
        @foreach (($fields ?? []) as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <noscript>
            <button type="submit" style="padding:12px 24px; background:#C8A968; color:#1F1B16; border:0; border-radius:6px; font-weight:600;">Continue to Payment</button>
        </noscript>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('payment-form').submit();
            }, 800);
        });
    </script>
</body>
</html>
