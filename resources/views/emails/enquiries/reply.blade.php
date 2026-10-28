<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $enquiry->subject ?: 'Your enquiry to Haarsingharr.pk' }}</title>
</head>
<body style="font-family: Georgia, 'Times New Roman', serif; background:#faf7f2; padding:32px;">
    <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e5dccb;border-radius:8px;padding:32px;">
        <p style="color:#a48b5f;letter-spacing:0.2em;text-transform:uppercase;font-size:11px;margin:0 0 16px;">Haarsingharr.pk — Customer Care</p>

        <p style="margin:0 0 16px;">Dear {{ $enquiry->name }},</p>

        <p style="margin:0 0 8px;">Thank you for contacting us regarding:</p>
        <p style="margin:0 0 24px;font-size:15px;"><em>{{ $enquiry->subject ?: $enquiry->message }}</em></p>

        <p style="margin:0 0 24px;line-height:1.6;white-space:pre-line;">{{ $reply }}</p>

        <p style="margin:0 0 4px;">With best regards,</p>
        <p style="margin:0;color:#6b6252;">The Haarsingharr.pk Team</p>
    </div>
</body>
</html>
