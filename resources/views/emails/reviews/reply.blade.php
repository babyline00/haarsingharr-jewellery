<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $review->product?->name ?? 'Your review' }} — response</title>
</head>
<body style="margin:0;padding:0;background:#f7f3eb;font-family:Georgia,'Times New Roman',serif;color:#241f1a;">
    <div style="max-width:620px;margin:0 auto;padding:36px 20px;">
        <div style="text-align:center;padding-bottom:24px;border-bottom:1px solid #e6ddce;">
            <div style="font-size:26px;letter-spacing:3px;text-transform:uppercase;">Haarsingharr</div>
            <div style="font-family:Arial,sans-serif;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#9b7b3f;margin-top:6px;">Fine Jewellery</div>
        </div>

        <div style="padding:28px 0;">
            <p style="font-size:16px;margin:0 0 14px;">Dear {{ $review->user?->first_name ?? $review->customer_name ?? 'Customer' }},</p>
            <p style="font-size:14px;line-height:1.7;margin:0 0 20px;">
                Thank you for sharing your thoughts on
                <strong>{{ $review->product?->name ?? 'your recent purchase' }}</strong>.
                Our team has responded to your review:
            </p>

            <div style="background:#ffffff;border:1px solid #e6ddce;border-radius:8px;padding:20px;font-family:Arial,sans-serif;font-size:14px;line-height:1.7;margin:0 0 20px;">
                <p style="margin:0 0 10px;color:#9b7b3f;">Your review</p>
                <p style="margin:0 0 14px;color:#6f6a63;">
                    @if ($review->title) "{{ $review->title }}" @endif
                    “{{ $review->body }}”
                </p>
                <p style="margin:0 0 10px;color:#9b7b3f;">Our response</p>
                <p style="margin:0;color:#241f1a;">{{ $review->admin_response }}</p>
            </div>

            @if ($review->product)
                <p style="margin:0 0 8px;">
                    <a href="{{ url('/products/'.$review->product->slug) }}"
                       style="display:inline-block;background:#241f1a;color:#f7f3eb;text-decoration:none;padding:12px 26px;border-radius:4px;font-family:Arial,sans-serif;font-size:13px;letter-spacing:1px;">
                        View the product
                    </a>
                </p>
            @endif

            <p style="font-size:13px;margin:24px 0 0;">
                Warm regards,<br>Team Haarsingharr
            </p>
        </div>

        <div style="border-top:1px solid #e6ddce;padding-top:18px;text-align:center;font-family:Arial,sans-serif;font-size:11px;color:#a39d94;">
            © {{ now()->year }} Haarsingharr.pk — Jewelry • For Every You
        </div>
    </div>
</body>
</html>
