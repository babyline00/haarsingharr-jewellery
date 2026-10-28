<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order {{ $order->number }}</title>
</head>
<body style="margin:0;padding:0;background:#f7f3eb;font-family:Georgia,'Times New Roman',serif;color:#241f1a;">
    <div style="max-width:620px;margin:0 auto;padding:36px 20px;">
        <div style="text-align:center;padding-bottom:24px;border-bottom:1px solid #e6ddce;">
            <div style="font-size:26px;letter-spacing:3px;text-transform:uppercase;">Haarsingharr</div>
            <div style="font-family:Arial,sans-serif;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#9b7b3f;margin-top:6px;">Fine Jewellery</div>
        </div>

        <div style="padding:28px 0;">
            <p style="font-size:16px;margin:0 0 14px;">Dear {{ $order->user?->first_name ?? 'Customer' }},</p>
            <p style="font-size:14px;line-height:1.7;margin:0 0 20px;">
                Thank you for your order. We have received it and will begin preparing your pieces shortly.
            </p>

            <table style="width:100%;font-family:Arial,sans-serif;font-size:13px;border-collapse:collapse;margin-bottom:20px;">
                <tr>
                    <td style="padding:10px 0;color:#6f6a63;border-bottom:1px solid #efe8db;">Order number</td>
                    <td style="padding:10px 0;text-align:right;border-bottom:1px solid #efe8db;"><strong>{{ $order->number }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:10px 0;color:#6f6a63;border-bottom:1px solid #efe8db;">Placed</td>
                    <td style="padding:10px 0;text-align:right;border-bottom:1px solid #efe8db;">{{ $order->created_at?->format('j F Y') }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;color:#6f6a63;border-bottom:1px solid #efe8db;">Payment</td>
                    <td style="padding:10px 0;text-align:right;border-bottom:1px solid #efe8db;">{{ $order->payment_status->label() }}</td>
                </tr>
                <tr>
                    <td style="padding:10px 0;color:#6f6a63;">Total</td>
                    <td style="padding:10px 0;text-align:right;font-size:16px;"><strong>Rs. {{ number_format((float) $order->grand_total) }}</strong></td>
                </tr>
            </table>

            <p style="font-family:Arial,sans-serif;font-size:12px;color:#6f6a63;line-height:1.7;margin:0 0 6px;text-transform:uppercase;letter-spacing:1px;">Shipping to</p>
            <p style="font-family:Arial,sans-serif;font-size:13px;line-height:1.7;margin:0 0 22px;">
                {{ $order->shippingAddress()?->fullAddress() ?? 'Address on file' }}
            </p>

            <p style="font-family:Arial,sans-serif;font-size:13px;line-height:1.7;margin:0 0 8px;">
                If you have any questions, reply to this email or message us on WhatsApp.
            </p>
            <p style="font-size:15px;margin:22px 0 4px;">With gratitude,</p>
            <p style="font-family:Arial,sans-serif;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:#9b7b3f;">The Haarsingharr Family</p>
        </div>

        <div style="border-top:1px solid #e6ddce;padding-top:16px;text-align:center;font-family:Arial,sans-serif;font-size:11px;color:#8a847c;">
            Haarsingharr Jewellers · Lahore, Pakistan
        </div>
    </div>
</body>
</html>
