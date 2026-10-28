<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Label {{ $shipment->order?->number }}</title>
    <style>
        @page { margin: 10px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #241f1a; font-size: 10px; line-height: 1.4; margin: 0; }
        .label { border: 2px solid #241f1a; padding: 12px; }
        .brand { font-family: Georgia, serif; font-size: 18px; letter-spacing: 2px; text-transform: uppercase; text-align: center; }
        .gold { text-align: center; font-size: 7px; letter-spacing: 2px; text-transform: uppercase; color: #9b7b3f; margin-top: 2px; }
        .rule { border-top: 1px solid #241f1a; margin: 8px 0; }
        .row { display: table; width: 100%; }
        .cell { display: table-cell; vertical-align: top; }
        .right { text-align: right; }
        .muted { color: #6f6a63; }
        .to { font-size: 8px; letter-spacing: 1px; text-transform: uppercase; color: #6f6a63; margin-bottom: 3px; }
        .name { font-size: 13px; font-weight: bold; }
        .address { margin-top: 3px; }
        .tracking { margin-top: 8px; border: 1px solid #241f1a; padding: 8px; text-align: center; }
        .tracking .number { font-size: 14px; font-weight: bold; letter-spacing: 1px; margin-top: 2px; }
        .meta { margin-top: 8px; font-size: 9px; }
        .meta td { padding: 1px 0; }
        .foot { margin-top: 8px; border-top: 1px solid #ddd3c3; padding-top: 5px; text-align: center; font-size: 7px; color: #6f6a63; }
    </style>
</head>
<body>
    @php
        $order = $shipment->order;
        $address = $order?->addresses->firstWhere('type', 'shipping');
    @endphp

    <div class="label">
        <div class="brand">Haarsingharr</div>
        <div class="gold">Fine Jewellery</div>

        <div class="rule"></div>

        <div class="to">Ship to</div>
        @if ($address)
            <div class="name">{{ $address->first_name }} {{ $address->last_name }}</div>
            <div class="address">{{ $address->fullAddress() }}</div>
            <div class="address">{{ $address->phone_country }} {{ $address->phone }}</div>
        @else
            <div class="muted">No shipping address on file</div>
        @endif

        <div class="tracking">
            <div class="muted" style="font-size:7px;letter-spacing:1px;text-transform:uppercase;">{{ $shipment->courier }} · {{ $shipment->method ?? 'standard' }}</div>
            <div class="number">{{ $shipment->tracking_number }}</div>
        </div>

        <table class="meta">
            <tr><td class="muted">Order</td><td class="right">{{ $order?->number }}</td></tr>
            <tr><td class="muted">Placed</td><td class="right">{{ $order?->created_at?->format('j M Y') }}</td></tr>
            <tr><td class="muted">Items</td><td class="right">{{ $order?->items->sum('quantity') }}</td></tr>
            <tr><td class="muted">COD</td><td class="right">{{ $order?->payment_method === 'cod' ? 'Rs. '.number_format((float) $order->grand_total, 2) : 'Prepaid' }}</td></tr>
        </table>

        <div class="foot">Haarsingharr Jewellers · Lahore, Pakistan</div>
    </div>
</body>
</html>
