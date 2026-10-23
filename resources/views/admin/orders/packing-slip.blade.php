@php
    $shipping = $order->shippingAddress();
    $totalUnits = $order->items->sum('quantity');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Packing Slip {{ $order->number }}</title>
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
        .items th { background: #f6f2ea; text-align: left; padding: 9px 10px; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; color: #6b5b30; border-bottom: 1px solid #e6ddc9; }
        .items td { padding: 11px 10px; border-bottom: 1px solid #eee6d6; }
        .items td.num, .items th.num { text-align: right; }
        .check { width: 22px; text-align: center; font-size: 15px; color: #c8a96a; }
        .summary { margin-top: 16px; font-size: 12px; }
        .note { margin-top: 22px; padding: 12px 14px; background: #faf7f1; border: 1px solid #eee6d6; border-radius: 4px; }
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
                <div class="doc-title">PACKING SLIP</div>
                <div class="meta">
                    Order № {{ $order->number }}<br>
                    {{ $order->created_at?->format('j M Y') }}<br>
                    {{ $totalUnits }} item{{ $totalUnits === 1 ? '' : 's' }}
                </div>
            </td>
        </tr>
    </table>

    <table class="party">
        <tr>
            <td>
                <div class="label">Ship to</div>
                @if ($shipping)
                    <div class="strong">{{ $shipping->first_name }} {{ $shipping->last_name }}</div>
                    <div class="muted">{{ $shipping->fullAddress() }}</div>
                    <div class="muted">{{ $shipping->phone_country }}{{ $shipping->phone }}</div>
                @else
                    <div class="strong">{{ $order->user?->name ?? 'Guest' }}</div>
                    <div class="muted">{{ $order->user?->email ?? $order->guest_email }}</div>
                @endif
            </td>
            <td>
                <div class="label">Shipping method</div>
                <div class="strong">{{ ucfirst($order->shipping_method ?? 'Standard') }}</div>
                @if ($order->tracking_number)
                    <div class="muted" style="margin-top:6px;">{{ strtoupper($order->courier) }} · {{ $order->tracking_number }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="check">✓</th>
                <th style="width:46%">Item</th>
                <th>SKU</th>
                <th class="num">Qty</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="check">☐</td>
                    <td>
                        <span class="strong">{{ $item->product_name }}</span>
                        @if ($item->variant_title)<br><span class="muted">{{ $item->variant_title }}</span>@endif
                    </td>
                    <td class="muted">{{ $item->sku ?? '—' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <span class="strong">Total units to pack: {{ $totalUnits }}</span>
    </div>

    @if ($order->customer_notes)
        <div class="note">
            <span class="label">Customer notes</span><br>
            {{ $order->customer_notes }}
        </div>
    @endif

    <div class="footer">
        Generated {{ now()->format('j M Y, g:i A') }} · {{ config('app.name') }}
    </div>
</div>
</body>
</html>
