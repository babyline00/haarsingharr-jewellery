<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>[Report] {{ $title }} — Haarsingharr.pk</title>
</head>
<body style="margin:0;background:#faf7f2;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:600px;margin:24px auto;background:#ffffff;border:1px solid #e5dccb;border-radius:8px;">
        <div style="padding:20px 24px;border-bottom:1px solid #e5dccb;background:#fdfaf4;border-radius:8px 8px 0 0;">
            <p style="color:#A48B5F;letter-spacing:0.25em;text-transform:uppercase;font-size:10px;margin:0 0 8px;">
                Haarsingharr Admin &middot; Reports
            </p>
            <h1 style="font-family:Georgia,'Times New Roman',serif;font-size:20px;font-weight:normal;margin:0;color:#221f22;">
                {{ $title }} — {{ $from }} &rarr; {{ $to }}
            </h1>
        </div>

        <div style="padding:24px;">
            <p style="font-size:14px;line-height:1.6;color:#565257;margin:0 0 12px;">
                Attached is an Excel workbook with the {{ $title }} report
                covering <strong>{{ $from }} &rarr; {{ $to }}</strong>.
            </p>
            <p style="font-size:13px;line-height:1.6;color:#8a858a;margin:0;">
                You can also open <a href="{{ config('app.url') ?? url('/') }}" style="color:#A48B5F;">the admin</a>,
                pick the same window and tinker with it live under <em>Reports</em>.
            </p>
        </div>

        <div style="padding:16px 24px;border-top:1px solid #e5dccb;background:#fdfaf4;border-radius:0 0 8px 8px;">
            <p style="font-size:11px;color:#8a858a;margin:0;">
                Sent by the reporting module of haarsingharr.pk — if you did not expect
                this email, contact a super-admin.
            </p>
        </div>
    </div>
</body>
</html>
