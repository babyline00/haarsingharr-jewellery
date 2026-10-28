<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Packing slips — {{ now()->format('j M Y') }}</title>
    <style>
        :root { --ink: #241f1a; --muted: #6f6a63; --line: #ddd3c3; --gold: #9b7b3f; --ivory: #f7f3eb; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: var(--ink); font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
        .toolbar { padding: 16px; text-align: right; background: var(--ivory); }
        .toolbar button { border: 1px solid var(--gold); background: var(--gold); color: #fff; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
        .slip { padding: 24px; page-break-after: always; }
        .slip:last-child { page-break-after: auto; }
        .head { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid var(--ink); padding-bottom: 14px; }
        .brand { font-family: Georgia, 'Times New Roman', serif; font-size: 24px; letter-spacing: 2px; text-transform: uppercase; }
        .brand small { display: block; font-family: Arial, sans-serif; font-size: 9px; letter-spacing: 2px; color: var(--gold); margin-top: 4px; }
        .meta { text-align: right; }
        .meta div { margin-bottom: 3px; }
        .muted { color: var(--muted); }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin: 20px 0; }
        h2 { font-family: Georgia, 'Times New Roman', serif; font-size: 13px; letter-spacing: 1px; text-transform: uppercase; margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); border-bottom: 1px solid var(--line); padding: 7px 5px; }
        td { padding: 8px 5px; border-bottom: 1px solid #f0ebe2; vertical-align: top; }
        td.right, th.right { text-align: right; }
        .totals { width: 280px; margin-left: auto; margin-top: 14px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .total { border-top: 1px solid var(--line); margin-top: 6px; padding-top: 8px; font-family: Georgia, serif; font-size: 15px; }
        .notes { margin-top: 16px; border: 1px solid var(--line); padding: 10px; font-size: 11px; color: var(--muted); }
        @media print { .toolbar { display: none; } .slip { padding: 0; } @page { size: A4; margin: 14mm; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print {{ $orders->count() }} packing slip(s)</button>
    </div>

    @foreach ($orders as $order)
        @php $shipping = $order->addresses->firstWhere('type', 'shipping'); @endphp
        <section class="slip">
            <div class="head">
                <div class="brand">Haarsingharr<small>Fine Jewellery</small></div>
                <div class="meta">
                    <div><strong>Packing slip</strong></div>
                    <div>{{ $order->number }}</div>
                    <div class="muted">{{ $order->created_at->format('j F Y') }}</div>
                    <div class="muted">{{ $order->items->sum('quantity') }} item(s)</div>
                </div>
            </div>

            <div class="grid">
                <div>
                    <h2>Ship to</h2>
                    @if ($shipping)
                        <div>{{ $shipping->first_name }} {{ $shipping->last_name }}</div>
                        <div class="muted">{{ $shipping->fullAddress() }}</div>
                        <div class="muted">{{ $shipping->phone_country }} {{ $shipping->phone }}</div>
                    @else
                        <div class="muted">No shipping address on file.</div>
                    @endif
                </div>
                <div>
                    <h2>Order details</h2>
                    <div>Status: {{ $order->status->label() }}</div>
                    <div>Payment: {{ $order->payment_status->label() }}</div>
                    <div>Channel: {{ ucfirst($order->order_channel ?? 'web') }}</div>
                    @if ($order->courier || $order->tracking_number)
                        <div class="muted">Courier: {{ $order->courier ?? '—' }}</div>
                        <div class="muted">Tracking: {{ $order->tracking_number ?? '—' }}</div>
                    @endif
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="right">Qty</th>
                        <th class="right">Unit</th>
                        <th class="right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                {{ $item->product_name }}
                                @if ($item->variant_title)
                                    <span class="muted">— {{ $item->variant_title }}</span>
                                @endif
                            </td>
                            <td class="muted">{{ $item->sku ?? '—' }}</td>
                            <td class="right">{{ $item->quantity }}</td>
                            <td class="right">Rs. {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="right">Rs. {{ number_format((float) $item->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div><span class="muted">Subtotal</span><span>Rs. {{ number_format((float) $order->subtotal, 2) }}</span></div>
                @if ((float) $order->discount_total > 0)
                    <div><span class="muted">Discount</span><span>-Rs. {{ number_format((float) $order->discount_total, 2) }}</span></div>
                @endif
                <div><span class="muted">Shipping</span><span>Rs. {{ number_format((float) $order->shipping_total, 2) }}</span></div>
                @if ((float) $order->tax_total > 0)
                    <div><span class="muted">Tax</span><span>Rs. {{ number_format((float) $order->tax_total, 2) }}</span></div>
                @endif
                <div class="total"><span>Total</span><span>Rs. {{ number_format((float) $order->grand_total, 2) }}</span></div>
            </div>

            @if ($order->customer_notes)
                <div class="notes"><strong>Customer notes:</strong> {{ $order->customer_notes }}</div>
            @endif

            <div class="notes">Thank you for choosing Haarsingharr. Please keep this slip with your parcel until delivery is complete.</div>
        </section>
    @endforeach
</body>
</html>
