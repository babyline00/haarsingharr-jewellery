@php
    $shipping = $order->shippingAddress();
    $billing = $order->billingAddress() ?: $shipping;
    $currency = \App\Models\StoreSetting::get('currency_symbol', 'Rs.');
    $placedAt = $order->created_at?->format('j M Y');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #2b2b2b; font-size: 12px; margin: 0; }
        .wrap { padding: 32px 36px; }
        .header { width: 100%; border-bottom: 2px solid #c8a96a; padding-bottom: 14px; margin-bottom: 22px; }
        .brand { font-size: 22px; font-weight: bold; letter-spacing: 1px; color: #1f1f1f; }
        .brand-sub { color: #8a8a8a; font-size: 10px; margin-top: 2px; }
        .doc-title { text-align: right; font-size: 18px; font-weight: bold; color: #c8a96a; }
        .meta { text-align: right; color: #6b6b6b; font-size: 10px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        .party { width: 100%; margin-bottom: 20px; }
        .party td { width: 50%; vertical-align: top; padding-right: 14px; }
        .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #9a9a9a; margin-bottom: 4px; }
        .strong { font-weight: bold; }
        .muted { color: #6b6b6b; }
        .items th { background: #f6f2ea; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #6b5b30; border-bottom: 1px solid #e6ddc9; }
        .items td { padding: 9px 10px; border-bottom: 1px solid #eee6d6; }
        .items td.num, .items th.num { text-align: right; }
        .totals { margin-top: 14px; width: 46%; margin-left: 54%; }
        .totals td { padding: 5px 10px; }
        .totals td.num { text-align: right; }
        .totals tr.grand td { border-top: 1px solid #c8a96a; font-size: 14px; font-weight: bold; padding-top: 9px; }
        .status { display: inline-block; padding: 3px 9px; border-radius: 10px; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; background: #f0ece1; color: #6b5b30; }
        .footer { margin-top: 34px; border-top: 1px solid #eee6d6; padding-top: 12px; font-size: 9px; color: #9a9a9a; text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ config('app.name') }}</div>
                <div class="brand-sub">Fine Jewellery &amp; Accessories</div>
            </td>
            <td style="text-align:right;">
                <div class="doc-title">INVOICE</div>
                <div class="meta">
                    № {{ $order->number }}<br>
                    Issued {{ $placedAt }}<br>
                    <span class="status">{{ $order->payment_status->label() }}</span>
                </div>
            </td>
        </tr>
    </table>

    <table class="party">
        <tr>
            <td>
                <div class="label">Billed to</div>
                @if ($billing)
                    <div class="strong">{{ $billing->first_name }} {{ $billing->last_name }}</div>
                    <div class="muted">{{ $billing->fullAddress() }}</div>
                    <div class="muted">{{ $billing->phone_country }}{{ $billing->phone }}</div>
                @else
                    <div class="strong">{{ $order->user?->name ?? 'Guest' }}</div>
                    <div class="muted">{{ $order->user?->email ?? $order->guest_email }}</div>
                @endif
            </td>
            <td>
                <div class="label">Ship to</div>
                @if ($shipping)
                    <div class="strong">{{ $shipping->first_name }} {{ $shipping->last_name }}</div>
                    <div class="muted">{{ $shipping->fullAddress() }}</div>
                    <div class="muted">{{ $shipping->phone_country }}{{ $shipping->phone }}</div>
                @else
                    <div class="muted">—</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:44%">Item</th>
                <th>SKU</th>
                <th class="num">Qty</th>
                <th class="num">Unit</th>
                <th class="num">Line total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>
                        <span class="strong">{{ $item->product_name }}</span>
                        @if ($item->variant_title)<br><span class="muted">{{ $item->variant_title }}</span>@endif
                    </td>
                    <td class="muted">{{ $item->sku ?? '—' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->unit_price, 2) }}</td>
                    <td class="num">{{ $currency }} {{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td class="muted">Subtotal</td><td class="num">{{ $currency }} {{ number_format($order->subtotal, 2) }}</td></tr>
        @if ($order->discount_total > 0)
            <tr><td class="muted">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td><td class="num">-{{ $currency }} {{ number_format($order->discount_total, 2) }}</td></tr>
        @endif
        <tr><td class="muted">Shipping</td><td class="num">{{ $currency }} {{ number_format($order->shipping_total, 2) }}</td></tr>
        @if ($order->tax_total > 0)
            <tr><td class="muted">Tax</td><td class="num">{{ $currency }} {{ number_format($order->tax_total, 2) }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="num">{{ $currency }} {{ number_format($order->grand_total, 2) }}</td></tr>
    </table>

    @if ($order->tracking_number)
        <div style="margin-top:26px;" class="muted">
            <span class="label">Shipment</span><br>
            {{ strtoupper($order->courier) }} · Tracking {{ $order->tracking_number }}
        </div>
    @endif

    <div class="footer">
        Thank you for shopping with {{ config('app.name') }}. This invoice was generated on {{ now()->format('j M Y, g:i A') }}.
    </div>
</div>
</body>
</html>
