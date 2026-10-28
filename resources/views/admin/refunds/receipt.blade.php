<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Refund receipt #{{ $refund->id }}</title>
    <style>
        @page { margin: 32px 40px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #241f1a; font-size: 11px; line-height: 1.5; }
        .brand { font-family: Georgia, serif; font-size: 26px; letter-spacing: 3px; text-transform: uppercase; text-align: center; }
        .tagline { text-align: center; font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #9b7b3f; margin-top: 4px; }
        .rule { border-top: 1px solid #ddd3c3; margin: 18px 0; }
        .title { font-family: Georgia, serif; font-size: 18px; margin: 0 0 2px; }
        .muted { color: #6f6a63; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 5px 0; vertical-align: top; }
        .meta td.label { color: #6f6a63; width: 42%; }
        .meta td.value { text-align: right; }
        .amount { background: #f7f3eb; border: 1px solid #e6ddce; padding: 16px; text-align: center; margin: 18px 0; }
        .amount .label { font-size: 9px; letter-spacing: 2px; text-transform: uppercase; color: #6f6a63; }
        .amount .value { font-family: Georgia, serif; font-size: 28px; margin-top: 4px; }
        .box { border: 1px solid #e6ddce; padding: 12px; margin-top: 12px; }
        .box h3 { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: #6f6a63; margin: 0 0 6px; }
        .foot { margin-top: 24px; text-align: center; font-size: 9px; color: #8a847c; }
    </style>
</head>
<body>
    <div class="brand">Haarsingharr</div>
    <div class="tagline">Fine Jewellery · Lahore</div>

    <div class="rule"></div>

    <table>
        <tr>
            <td>
                <h1 class="title">Refund receipt</h1>
                <div class="muted">Receipt #{{ str_pad((string) $refund->id, 6, '0', STR_PAD_LEFT) }}</div>
            </td>
            <td style="text-align:right">
                <div class="muted">Issued {{ ($refund->processed_at ?? now())->format('j F Y') }}</div>
                <div class="muted">Status: {{ ucwords(str_replace('_', ' ', $refund->status)) }}</div>
            </td>
        </tr>
    </table>

    <div class="amount">
        <div class="label">Amount refunded</div>
        <div class="value">Rs. {{ number_format((float) $refund->amount, 2) }}</div>
        <div class="muted">{{ $refund->currency ?? $refund->payment?->currency ?? 'PKR' }} · {{ ucfirst($refund->type) }} refund</div>
    </div>

    <table class="meta">
        <tr><td class="label">Customer</td><td class="value">{{ $refund->order?->user?->name ?? $refund->order?->shippingAddress()?->first_name ?? 'Guest customer' }}</td></tr>
        <tr><td class="label">Order number</td><td class="value">{{ $refund->order?->number ?? '—' }}</td></tr>
        <tr><td class="label">Payment reference</td><td class="value">{{ $refund->payment?->provider_transaction_ref ?? $refund->provider_ref ?? '—' }}</td></tr>
        <tr><td class="label">Provider</td><td class="value">{{ ucfirst(str_replace('_', ' ', $refund->payment?->provider ?? 'manual')) }}</td></tr>
        <tr><td class="label">Approved by</td><td class="value">{{ $refund->approver?->name ?? 'Haarsingharr staff' }}</td></tr>
    </table>

    <div class="box">
        <h3>Reason</h3>
        <div>{{ $refund->reason }}</div>
    </div>

    @if ($refund->provider_ref)
        <div class="box">
            <h3>Settlement reference</h3>
            <div>{{ $refund->provider_ref }}</div>
        </div>
    @endif

    <div class="rule"></div>

    <p class="muted" style="margin:0">
        This receipt confirms that Haarsingharr has processed the refund above. Bank or wallet posting times
        may vary by provider. Please retain this receipt for your records.
    </p>

    <div class="foot">
        Haarsingharr Jewellers · Lahore, Pakistan<br>
        This is a system-generated receipt.
    </div>
</body>
</html>
