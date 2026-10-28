<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Template test send') }} — {{ $name }}</title>
</head>
<body style="margin:0;background:#faf7f2;">
    <div style="max-width:640px;margin:32px auto;background:#fff;border:1px solid #e5dccb;border-radius:8px;padding:24px;">
        <p style="color:#a48b5f;letter-spacing:0.2em;text-transform:uppercase;font-size:10px;margin:0 0 16px;">
            TEST SEND — {{ $name }}
        </p>
        <div>{{ $bodyHtml }}</div>
    </div>
</body>
</html>
