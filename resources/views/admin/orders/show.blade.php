@extends('admin.layout')

@section('title', 'Order ' . $order->number . ' — Haarsingharr Admin')

@php
    $shippingAddress = $order->addresses->firstWhere('type', 'shipping');
    $billingAddress = $order->addresses->firstWhere('type', 'billing');
    $outstanding = $order->outstandingAmount();
    $refundablePayments = $order->payments->mapWithKeys(function ($payment) {
        $committed = $payment->refunds
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->sum('amount');

        return [$payment->id => max(0, (float) $payment->amount - (float) $payment->amount_refunded - (float) $committed)];
    });
@endphp

@section('content')
    <x-admin.header :title="$order->number" :subtitle="'Placed '.$order->created_at->format('j F Y, g:i A').' · '.($order->user?->name ?? $order->guest_email ?? 'Guest customer')">
        <x-slot:actions>
            <x-admin.button :href="route('admin.orders.invoice', $order)" variant="outline" size="sm" icon="file-text" target="_blank">
                Invoice
            </x-admin.button>
            <x-admin.button :href="route('admin.orders.packing_slip', $order)" variant="outline" size="sm" icon="printer" target="_blank">
                Packing slip
            </x-admin.button>
            <x-admin.button type="button" variant="outline" size="sm" icon="mail"
                            x-on:click="$dispatch('open-modal-email-order')">
                Resend email
            </x-admin.button>
            <x-admin.button type="button" variant="primary" size="sm" icon="plus"
                            x-on:click="$dispatch('open-modal-manual-payment')">
                Record payment
            </x-admin.button>
        </x-slot:actions>

        <x-slot:meta>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <x-admin.status-pill :status="$order->status->value" :label="$order->status->label()" />
                <x-admin.status-pill :status="$order->payment_status->value" :label="$order->payment_status->label()" />
                <span class="badge badge-charcoal capitalize">{{ $order->fulfilment_status ?? 'unfulfilled' }}</span>
                <span class="text-muted">{{ $order->items->sum('quantity') }} item(s)</span>
                <span class="text-muted">·</span>
                <span class="text-muted capitalize">{{ $order->order_channel ?? 'web' }}</span>
            </div>
        </x-slot:meta>
    </x-admin.header>

<<<<<<< HEAD
    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            {{-- Items + fulfilment --}}
            <x-admin.card title="Items" subtitle="Update fulfilment per line. A partial state is saved automatically." :padding="false">
                <x-slot:actions>
                    @if ($order->shipments->isEmpty())
                        <x-admin.button type="button" variant="outline" size="sm" icon="truck"
                                        x-on:click="$dispatch('open-modal-shipment-create')">
                            Add shipment
                        </x-admin.button>
                    @endif
                </x-slot:actions>
=======
        {{-- Items --}}
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Items</h2>
            </div>
            <div class="divide-y divide-charcoal/5">
                @foreach ($order->items as $item)
                    <div class="p-4 flex gap-3 items-center">
                        <div class="w-14 h-14 bg-ivory-alt rounded-md overflow-hidden flex-shrink-0">
                            @if ($item->product_image) <img src="{{ \App\Support\Media::url($item->product_image) }}" alt="" class="w-full h-full object-cover"> @endif
                        </div>
                        <div class="flex-1">
                            <div class="font-medium text-sm">{{ $item->product_name }}</div>
                            @if ($item->variant_title) <div class="text-xs text-muted">{{ $item->variant_title }}</div> @endif
                            <div class="text-xs text-muted">SKU: {{ $item->sku ?? '—' }} • Qty: {{ $item->quantity }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium text-sm">Rs. {{ number_format($item->line_total) }}</div>
                            <div class="text-xs text-muted">Rs. {{ number_format($item->unit_price) }} each</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-5 border-t border-charcoal/5 space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-muted">Subtotal</span><span>Rs. {{ number_format($order->subtotal) }}</span></div>
                @if ($order->discount_total > 0)
                    <div class="flex justify-between text-success"><span class="text-muted">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span><span>-Rs. {{ number_format($order->discount_total) }}</span></div>
                @endif
                <div class="flex justify-between"><span class="text-muted">Shipping</span><span>Rs. {{ number_format($order->shipping_total) }}</span></div>
                <div class="flex justify-between font-serif text-lg pt-2 border-t border-charcoal/5"><span>Total</span><span>Rs. {{ number_format($order->grand_total) }}</span></div>
            </div>
        </div>
>>>>>>> origin/main

                <x-admin.table caption="Order items">
                    <thead>
                        <tr>
                            <x-admin.th label="Product" />
                            <x-admin.th label="SKU" />
                            <x-admin.th label="Qty" align="center" />
                            <x-admin.th label="Unit" align="right" />
                            <x-admin.th label="Line total" align="right" />
                            <x-admin.th label="Fulfilment" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="p-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-md bg-ivory-alt overflow-hidden flex-shrink-0">
                                            @if ($item->product_image)
                                                <img src="{{ \App\Support\Media::url($item->product_image) }}" alt="" class="w-full h-full object-cover">
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium">{{ $item->product_name }}</div>
                                            @if ($item->variant_title)
                                                <div class="text-xs text-muted">{{ $item->variant_title }}</div>
                                            @endif
                                            @if ($item->refunded_quantity > 0)
                                                <span class="text-xs text-danger">{{ $item->refunded_quantity }} refunded</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-3 text-xs text-muted">{{ $item->sku ?? '—' }}</td>
                                <td class="p-3 text-center text-sm">{{ $item->quantity }}</td>
                                <td class="p-3 text-right text-sm"><x-admin.money :amount="$item->unit_price" :decimals="2" /></td>
                                <td class="p-3 text-right text-sm font-medium"><x-admin.money :amount="$item->line_total" :decimals="2" /></td>
                                <td class="p-3">
                                    <form method="POST" action="{{ route('admin.orders.items.fulfilment', [$order, $item->id]) }}"
                                          class="flex items-center gap-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <label for="item-fulfilment-{{ $item->id }}" class="sr-only">Fulfilment for {{ $item->product_name }}</label>
                                        <select id="item-fulfilment-{{ $item->id }}" name="fulfilment_status" class="text-xs py-1.5 min-w-[112px]">
                                            @foreach ($itemStatuses as $status)
                                                <option value="{{ $status }}" @selected($item->fulfilment_status === $status)>
                                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn-ghost btn-sm" aria-label="Save fulfilment for {{ $item->product_name }}">
                                            <x-admin.icon name="check" :size="14" />
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-admin.table>

                <div class="p-5 border-t border-charcoal/5 space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-muted">Subtotal</span><x-admin.money :amount="$order->subtotal" :decimals="2" /></div>
                    @if ((float) $order->discount_total > 0)
                        <div class="flex justify-between text-success">
                            <span class="text-muted">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</span>
                            <span>-<x-admin.money :amount="$order->discount_total" :decimals="2" /></span>
                        </div>
                    @endif
                    <div class="flex justify-between"><span class="text-muted">Shipping</span><x-admin.money :amount="$order->shipping_total" :decimals="2" /></div>
                    @if ((float) $order->tax_total > 0)
                        <div class="flex justify-between"><span class="text-muted">Tax</span><x-admin.money :amount="$order->tax_total" :decimals="2" /></div>
                    @endif
                    <div class="flex justify-between font-serif text-lg pt-2 border-t border-charcoal/5">
                        <span>Total</span><x-admin.money :amount="$order->grand_total" :decimals="2" />
                    </div>
                    @if ((float) $order->paid_amount > 0 || (float) $order->refunded_amount > 0)
                        <div class="flex justify-between text-xs text-muted pt-1">
                            <span>Paid</span><x-admin.money :amount="$order->paid_amount" :decimals="2" />
                        </div>
                        <div class="flex justify-between text-xs text-muted">
                            <span>Refunded</span><x-admin.money :amount="$order->refunded_amount" :decimals="2" />
                        </div>
                    @endif
                </div>
            </x-admin.card>

            {{-- Timeline --}}
            <x-admin.card title="Status timeline" subtitle="Every status change, actor and note.">
                <div class="space-y-4">
                    @forelse ($order->statusHistory as $history)
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-champagne mt-2 flex-shrink-0"></div>
                            <div class="min-w-0">
                                <div class="text-sm text-charcoal-soft">
                                    <span class="font-medium capitalize">{{ str_replace('_', ' ', $history->new_status) }}</span>
                                    @if ($history->previous_status)
                                        <span class="text-muted text-xs">from {{ str_replace('_', ' ', $history->previous_status) }}</span>
                                    @endif
                                    @if ($history->actor)
                                        <span class="text-muted text-xs">by {{ $history->actor->name }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-muted">{{ $history->created_at->format('j M Y, g:i A') }}</div>
                                @if ($history->note)
                                    <p class="text-xs text-muted mt-1">{{ $history->note }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No status changes yet.</p>
                    @endforelse
                </div>
            </x-admin.card>

            {{-- Internal notes --}}
            <x-admin.card title="Internal notes" subtitle="Only staff can see these notes.">
                <form method="POST" action="{{ route('admin.orders.notes.store', $order) }}" class="space-y-2 mb-5">
                    @csrf
                    <label for="order-note" class="sr-only">Internal note</label>
                    <textarea id="order-note" name="body" rows="3" required maxlength="2000"
                              placeholder="Add context for the next person handling this order…">{{ old('body') }}</textarea>
                    <div class="flex justify-end">
                        <x-admin.button type="submit" size="sm" variant="outline" icon="note">Add note</x-admin.button>
                    </div>
                </form>

                <div class="space-y-4">
                    @forelse ($order->notes as $note)
                        <div class="flex gap-3 border-t border-charcoal/5 pt-4 first:border-0 first:pt-0">
                            <div class="w-8 h-8 rounded-full bg-ivory-alt flex items-center justify-center flex-shrink-0 text-xs font-serif text-champagne-dark">
                                {{ strtoupper(substr($note->authorName(), 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <div class="text-sm font-medium">{{ $note->authorName() }}</div>
                                    <div class="text-xs text-muted">{{ $note->created_at->format('j M Y, g:i A') }}</div>
                                </div>
                                <p class="text-sm text-charcoal-soft mt-1 whitespace-pre-line">{{ $note->body }}</p>
                                <x-admin.confirm :action="route('admin.orders.notes.destroy', [$order, $note])"
                                                 method="DELETE"
                                                 title="Delete this note?"
                                                 message="The note will be removed from the order timeline."
                                                 label="Delete"
                                                 confirm-label="Delete note" />
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No internal notes yet.</p>
                    @endforelse
                </div>
            </x-admin.card>
        </div>

        <aside class="space-y-5">
            {{-- Customer --}}
            <x-admin.card title="Customer">
                <div class="text-sm space-y-1">
                    <div class="font-medium">{{ $order->user?->name ?? trim(($shippingAddress?->first_name ?? '').' '.($shippingAddress?->last_name ?? '')) ?: 'Guest' }}</div>
                    <div class="text-muted">{{ $order->user?->email ?? $order->guest_email ?? 'No email' }}</div>
                    <div class="text-muted">{{ $order->guest_phone ?? $order->user?->phone ?? 'No phone' }}</div>
                    @if ($order->user)
                        <a href="{{ route('admin.customers.show', $order->user) }}" class="text-xs text-champagne-dark hover:underline">View customer profile →</a>
                    @endif
                </div>

                <div class="mt-5 pt-4 border-t border-charcoal/5 space-y-4">
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-xs uppercase tracking-widest text-muted">Shipping address</h3>
                            <button type="button" class="text-xs text-champagne-dark hover:underline"
                                    x-on:click="$dispatch('open-modal-address-shipping')">Edit</button>
                        </div>
                        @if ($shippingAddress)
                            <p class="text-sm text-charcoal-soft mt-1.5">{{ $shippingAddress->fullAddress() }}</p>
                            <p class="text-xs text-muted mt-1">{{ $shippingAddress->phone_country }} {{ $shippingAddress->phone }}</p>
                        @else
                            <p class="text-sm text-muted mt-1.5">No shipping address on file.</p>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-xs uppercase tracking-widest text-muted">Billing address</h3>
                            <button type="button" class="text-xs text-champagne-dark hover:underline"
                                    x-on:click="$dispatch('open-modal-address-billing')">Edit</button>
                        </div>
                        @if ($billingAddress)
                            <p class="text-sm text-charcoal-soft mt-1.5">{{ $billingAddress->fullAddress() }}</p>
                        @else
                            <p class="text-sm text-muted mt-1.5">No billing address on file.</p>
                        @endif
                    </div>
                </div>
            </x-admin.card>

            {{-- Status --}}
            <x-admin.card title="Order status">
                @if (count($allowedTransitions))
                    <form method="POST" action="{{ route('admin.orders.status.update', $order) }}" class="space-y-3">
                        @csrf
                        <x-admin.field name="status" label="Move to">
                            <x-admin.select name="status" :options="collect($allowedTransitions)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
                        </x-admin.field>
                        <x-admin.field name="note" label="Note (optional)">
                            <x-admin.textarea name="note" rows="2" />
                        </x-admin.field>
                        <x-admin.button type="submit" size="sm" icon="check">Update status</x-admin.button>
                    </form>
                @else
                    <p class="text-sm text-muted">This order is in a terminal state. No further transitions are available.</p>
                @endif

                @if ($order->status->isCancellable())
                    <div class="mt-4 pt-4 border-t border-charcoal/5">
                        <button type="button" class="btn-outline btn-sm w-full"
                                x-on:click="$dispatch('open-modal-cancel-order')">
                            Cancel order &amp; reconcile stock
                        </button>
                    </div>
                @endif
            </x-admin.card>

            {{-- Shipments --}}
            <x-admin.card title="Shipments" subtitle="Courier, tracking and delivery milestones." :padding="false">
                <x-slot:actions>
                    <button type="button" class="text-xs text-champagne-dark hover:underline"
                            x-on:click="$dispatch('open-modal-shipment-create')">Add</button>
                </x-slot:actions>

                @if ($order->shipments->isEmpty())
                    <div class="p-5 text-sm text-muted">
                        No shipment recorded yet. Adding one updates the order's courier and tracking fields.
                    </div>
                @else
                    <div class="divide-y divide-charcoal/5">
                        @foreach ($order->shipments as $shipment)
                            <div class="p-4 space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="text-sm font-medium capitalize">{{ $shipment->courier }}</div>
                                        <div class="text-xs text-muted">{{ $shipment->method ?? 'Standard' }}</div>
                                    </div>
                                    <x-admin.status-pill :status="$shipment->status" :label="$shipment->statusLabel()" />
                                </div>
                                <div class="text-xs text-muted">Tracking: <span class="text-charcoal">{{ $shipment->tracking_number }}</span></div>
                                @if ($shipment->estimated_delivery_at)
                                    <div class="text-xs text-muted">Est. delivery {{ $shipment->estimated_delivery_at->format('j M Y') }}</div>
                                @endif
                                @if ($shipment->notes)
                                    <p class="text-xs text-muted whitespace-pre-line">{{ $shipment->notes }}</p>
                                @endif
                                <div class="flex flex-wrap items-center gap-3 pt-1">
                                    <a href="{{ route('admin.shipping.shipments.label', $shipment) }}" class="text-xs text-champagne-dark hover:underline">Label PDF</a>
                                    @if ($shipment->tracking_url)
                                        <a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener" class="text-xs text-champagne-dark hover:underline">Track →</a>
                                    @endif
                                    <button type="button" class="text-xs text-champagne-dark hover:underline"
                                            x-on:click="$dispatch('open-modal-shipment-edit-{{ $shipment->id }}')">Edit</button>
                                    <x-admin.confirm :action="route('admin.orders.shipments.destroy', [$order, $shipment])"
                                                     method="DELETE"
                                                     title="Remove this shipment?"
                                                     message="The shipment record is deleted and the order tracking fields are cleared."
                                                     label="Remove"
                                                     confirm-label="Remove shipment" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            {{-- Payments --}}
            <x-admin.card title="Payments" subtitle="Captured and pending transactions." :padding="false">
                <x-slot:actions>
                    @if ($outstanding > 0)
                        <button type="button" class="text-xs text-champagne-dark hover:underline"
                                x-on:click="$dispatch('open-modal-manual-payment')">Record</button>
                    @endif
                </x-slot:actions>

                @if ($order->payments->isEmpty())
                    <div class="p-5 text-sm text-muted">No payments recorded. Outstanding balance: <x-admin.money :amount="$outstanding" :decimals="2" />.</div>
                @else
                    <div class="divide-y divide-charcoal/5">
                        @foreach ($order->payments as $payment)
                            @php $refundable = $refundablePayments[$payment->id] ?? 0; @endphp
                            <div class="p-4 space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <div class="text-sm font-medium capitalize">{{ str_replace('_', ' ', $payment->provider) }}</div>
                                        <div class="text-xs text-muted">{{ $payment->provider_transaction_ref ?? 'No reference' }}</div>
                                    </div>
                                    <x-admin.status-pill :status="$payment->status" :label="ucwords(str_replace('_', ' ', $payment->status))" />
                                </div>
                                <div class="flex justify-between text-xs text-muted">
                                    <span>Amount</span>
                                    <x-admin.money :amount="$payment->amount" :decimals="2" />
                                </div>
                                <div class="flex justify-between text-xs text-muted">
                                    <span>Refunded</span>
                                    <x-admin.money :amount="$payment->amount_refunded" :decimals="2" />
                                </div>
                                <div class="flex flex-wrap items-center gap-3 pt-1">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-xs text-champagne-dark hover:underline">Payment detail</a>
                                    @can('refunds.create')
                                        @if ($refundable > 0)
                                            <button type="button" class="text-xs text-champagne-dark hover:underline"
                                                    x-on:click="$dispatch('open-modal-refund-{{ $payment->id }}')">Request refund</button>
                                        @endif
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            {{-- Refunds / returns --}}
            <x-admin.card title="Refunds" subtitle="Money returned against this order." :padding="false">
                @if ($order->refunds->isEmpty())
                    <div class="p-5 text-sm text-muted">No refunds requested.</div>
                @else
                    <div class="divide-y divide-charcoal/5">
                        @foreach ($order->refunds as $refund)
                            <a href="{{ route('admin.refunds.show', $refund) }}"
                               class="flex items-center justify-between gap-3 p-4 hover:bg-ivory-alt/30 transition-colors">
                                <div>
                                    <div class="text-sm font-medium"><x-admin.money :amount="$refund->amount" :decimals="2" /></div>
                                    <div class="text-xs text-muted capitalize">{{ $refund->type }} · {{ $refund->created_at->format('j M Y') }}</div>
                                </div>
                                <x-admin.status-pill :status="$refund->status" :label="ucwords(str_replace('_', ' ', $refund->status))" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card title="Returns & exchanges" subtitle="After-sales requests linked to this order." :padding="false">
                <x-slot:actions>
                    @can('exchanges.create')
                        <button type="button" class="text-xs text-champagne-dark hover:underline"
                                x-on:click="$dispatch('open-modal-exchange-create')">Create</button>
                    @endcan
                </x-slot:actions>

                @if ($order->exchangeRequests->isEmpty())
                    <div class="p-5 text-sm text-muted">No return or exchange requests.</div>
                @else
                    <div class="divide-y divide-charcoal/5">
                        @foreach ($order->exchangeRequests as $exchange)
                            <a href="{{ route('admin.exchanges.show', $exchange) }}"
                               class="flex items-center justify-between gap-3 p-4 hover:bg-ivory-alt/30 transition-colors">
                                <div>
                                    <div class="text-sm font-medium">{{ $exchange->number }}</div>
                                    <div class="text-xs text-muted capitalize">{{ $exchange->type }} · {{ $exchange->quantity }} item(s)</div>
                                </div>
                                <x-admin.status-pill :status="$exchange->status" :label="ucwords(str_replace('_', ' ', $exchange->status))" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>
        </aside>
    </div>

    {{-- Modals --}}
    @include('admin.orders._address-modal', ['type' => 'shipping', 'order' => $order])
    @include('admin.orders._address-modal', ['type' => 'billing', 'order' => $order])

    <x-admin.modal name="email-order" title="Resend customer email" max-width="max-w-md">
        <p class="text-sm text-muted mb-4">
            Send to <strong>{{ $order->user?->email ?? $order->guest_email ?? 'no email on file' }}</strong>.
            The invoice email includes a PDF attachment.
        </p>
        <form method="POST" action="{{ route('admin.orders.emails.send', $order) }}" class="flex flex-wrap gap-2">
            @csrf
            <button type="submit" name="type" value="confirmation" class="btn-outline btn-sm">Resend confirmation</button>
            <button type="submit" name="type" value="invoice" class="btn-gold btn-sm">Email invoice PDF</button>
        </form>
    </x-admin.modal>

    <x-admin.modal name="cancel-order" title="Cancel order & reconcile stock" max-width="max-w-md">
        <p class="text-sm text-muted mb-4">
            Reserved stock is released; stock already deducted is returned to inventory via the ledger.
            This cannot be undone.
        </p>
        <form method="POST" action="{{ route('admin.orders.cancel', $order) }}">
            @csrf
            <x-admin.field name="reason" label="Reason">
                <x-admin.textarea name="reason" rows="3" placeholder="Why is this order being cancelled?" />
            </x-admin.field>
            <div class="flex justify-end gap-2 mt-4">
                <x-admin.button type="button" variant="outline" size="sm"
                                x-on:click="$dispatch('close-modal-cancel-order')">Keep order</x-admin.button>
                <x-admin.button type="submit" variant="danger" size="sm">Cancel order</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <x-admin.modal name="manual-payment" title="Record a manual payment" max-width="max-w-lg">
        <form method="POST" action="{{ route('admin.orders.payments.store', $order) }}" class="space-y-3">
            @csrf
            <div class="grid sm:grid-cols-2 gap-3">
                <x-admin.field name="provider" label="Provider" required>
                    <x-admin.select name="provider" :options="[
                        'cod' => 'Cash on delivery',
                        'bank_transfer' => 'Bank transfer',
                        'jazzcash' => 'JazzCash',
                        'easypaisa' => 'Easypaisa',
                        'other' => 'Other',
                    ]" />
                </x-admin.field>
                <x-admin.field name="method" label="Method">
                    <x-admin.input name="method" placeholder="cash, wallet, bank…" />
                </x-admin.field>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <x-admin.field name="amount" label="Amount (PKR)" required hint="Outstanding: Rs. {{ number_format($outstanding, 2) }}">
                    <x-admin.input name="amount" type="number" step="0.01" min="0.01" :value="number_format($outstanding, 2, '.', '')" required />
                </x-admin.field>
                <x-admin.field name="reference" label="Reference">
                    <x-admin.input name="reference" placeholder="Transaction / receipt #" />
                </x-admin.field>
            </div>
            <x-admin.field name="note" label="Note">
                <x-admin.textarea name="note" rows="2" />
            </x-admin.field>
            <x-admin.checkbox name="mark_paid" label="Mark this payment captured now" checked />
            <div class="flex justify-end gap-2 pt-2">
                <x-admin.button type="button" variant="outline" size="sm"
                                x-on:click="$dispatch('close-modal-manual-payment')">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm" icon="wallet">Record payment</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    <x-admin.modal name="shipment-create" title="Add shipment" max-width="max-w-lg">
        <form method="POST" action="{{ route('admin.orders.shipments.store', $order) }}" class="space-y-3">
            @csrf
            <div class="grid sm:grid-cols-2 gap-3">
                <x-admin.field name="courier" label="Courier" required>
                    <x-admin.input name="courier" list="courier-options" placeholder="tcs, leopards, dhl…" required />
                    <datalist id="courier-options">
                        @foreach (['tcs', 'leopards', 'dhl', 'fedex', 'postex', 'm&p'] as $courier)
                            <option value="{{ $courier }}"></option>
                        @endforeach
                    </datalist>
                </x-admin.field>
                <x-admin.field name="method" label="Service">
                    <x-admin.input name="method" placeholder="standard, express…" />
                </x-admin.field>
            </div>
            <x-admin.field name="tracking_number" label="Tracking number" required>
                <x-admin.input name="tracking_number" required />
            </x-admin.field>
            <x-admin.field name="tracking_url" label="Tracking URL">
                <x-admin.input name="tracking_url" type="url" placeholder="https://…" />
            </x-admin.field>
            <div class="grid sm:grid-cols-3 gap-3">
                <x-admin.field name="shipping_cost" label="Cost (PKR)">
                    <x-admin.input name="shipping_cost" type="number" min="0" step="0.01" />
                </x-admin.field>
                <x-admin.field name="weight_grams" label="Weight (g)">
                    <x-admin.input name="weight_grams" type="number" min="0" step="0.01" />
                </x-admin.field>
                <x-admin.field name="estimated_delivery_at" label="Est. delivery">
                    <x-admin.input name="estimated_delivery_at" type="date" />
                </x-admin.field>
            </div>
            <x-admin.field name="notes" label="Notes">
                <x-admin.textarea name="notes" rows="2" />
            </x-admin.field>
            <div class="flex justify-end gap-2 pt-1">
                <x-admin.button type="button" variant="outline" size="sm"
                                x-on:click="$dispatch('close-modal-shipment-create')">Cancel</x-admin.button>
                <x-admin.button type="submit" size="sm" icon="truck">Save shipment</x-admin.button>
            </div>
        </form>
    </x-admin.modal>

    @foreach ($order->shipments as $shipment)
        <x-admin.modal name="shipment-edit-{{ $shipment->id }}" title="Edit shipment" max-width="max-w-lg">
            <form method="POST" action="{{ route('admin.orders.shipments.update', [$order, $shipment]) }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-admin.field name="courier" label="Courier" required>
                        <x-admin.input name="courier" :value="$shipment->courier" required />
                    </x-admin.field>
                    <x-admin.field name="method" label="Service">
                        <x-admin.input name="method" :value="$shipment->method" />
                    </x-admin.field>
                </div>
                <x-admin.field name="tracking_number" label="Tracking number" required>
                    <x-admin.input name="tracking_number" :value="$shipment->tracking_number" required />
                </x-admin.field>
                <x-admin.field name="tracking_url" label="Tracking URL">
                    <x-admin.input name="tracking_url" type="url" :value="$shipment->tracking_url" />
                </x-admin.field>
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-admin.field name="status" label="Shipment status" required>
                        <x-admin.select name="status" :selected="$shipment->status" :options="collect(\App\Services\ShipmentService::STATUSES)->mapWithKeys(fn ($status) => [$status => ucwords(str_replace('_', ' ', $status))])->all()" />
                    </x-admin.field>
                    <x-admin.field name="estimated_delivery_at" label="Est. delivery">
                        <x-admin.input name="estimated_delivery_at" type="date"
                                       :value="$shipment->estimated_delivery_at?->toDateString()" />
                    </x-admin.field>
                </div>
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-admin.field name="shipping_cost" label="Cost (PKR)">
                        <x-admin.input name="shipping_cost" type="number" min="0" step="0.01" :value="$shipment->shipping_cost" />
                    </x-admin.field>
                    <x-admin.field name="weight_grams" label="Weight (g)">
                        <x-admin.input name="weight_grams" type="number" min="0" step="0.01" :value="$shipment->weight_grams" />
                    </x-admin.field>
                </div>
                <x-admin.field name="notes" label="Notes">
                    <x-admin.textarea name="notes" rows="2" :value="$shipment->notes" />
                </x-admin.field>
                <div class="flex justify-end gap-2 pt-1">
                    <x-admin.button type="button" variant="outline" size="sm"
                                    x-on:click="$dispatch('close-modal-shipment-edit-{{ $shipment->id }}')">Cancel</x-admin.button>
                    <x-admin.button type="submit" size="sm" icon="save">Save changes</x-admin.button>
                </div>
            </form>
        </x-admin.modal>
    @endforeach

    @can('refunds.create')
        @foreach ($order->payments as $payment)
            @php $refundable = $refundablePayments[$payment->id] ?? 0; @endphp
            @if ($refundable > 0)
                <x-admin.modal name="refund-{{ $payment->id }}" title="Request refund" max-width="max-w-lg">
                    <form method="POST" action="{{ route('admin.refunds.store') }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                        <div class="rounded-md bg-ivory-alt/60 p-3 text-xs text-muted">
                            {{ ucfirst($payment->provider) }} · Refundable
                            <strong class="text-charcoal"><x-admin.money :amount="$refundable" :decimals="2" /></strong>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <x-admin.field name="amount" label="Amount (PKR)" required>
                                <x-admin.input name="amount" type="number" min="0.01" step="0.01"
                                               :value="number_format($refundable, 2, '.', '')" required />
                            </x-admin.field>
                            <x-admin.field name="type" label="Refund type" required>
                                <x-admin.select name="type" :options="['full' => 'Full payment refund', 'partial' => 'Partial refund']" />
                            </x-admin.field>
                        </div>
                        <x-admin.field name="reason" label="Reason" required>
                            <x-admin.textarea name="reason" rows="2" required />
                        </x-admin.field>
                        <x-admin.field name="notes" label="Internal notes">
                            <x-admin.textarea name="notes" rows="2" />
                        </x-admin.field>
                        <x-admin.checkbox name="restock" label="Return the pieces to sellable stock when the refund completes (full refunds only)" />
                        <div class="flex justify-end gap-2 pt-1">
                            <x-admin.button type="button" variant="outline" size="sm"
                                            x-on:click="$dispatch('close-modal-refund-{{ $payment->id }}')">Cancel</x-admin.button>
                            <x-admin.button type="submit" size="sm" icon="rotate-ccw">Request refund</x-admin.button>
                        </div>
                    </form>
                </x-admin.modal>
            @endif
        @endforeach
    @endcan

    @can('exchanges.create')
        <x-admin.modal name="exchange-create" title="Create return or exchange" max-width="max-w-lg">
            <form method="POST" action="{{ route('admin.exchanges.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                <div class="grid sm:grid-cols-2 gap-3">
                    <x-admin.field name="type" label="Request type" required>
                        <x-admin.select name="type" :options="['return' => 'Return', 'exchange' => 'Exchange', 'refund' => 'Return with refund']" />
                    </x-admin.field>
                    <x-admin.field name="order_item_id" label="Item">
                        <x-admin.select name="order_item_id" placeholder="Whole order"
                                        :options="$order->items->mapWithKeys(fn ($item) => [$item->id => $item->product_name.' × '.$item->quantity])->all()" />
                    </x-admin.field>
                </div>
                <x-admin.field name="quantity" label="Quantity" required>
                    <x-admin.input name="quantity" type="number" min="1" value="1" required />
                </x-admin.field>
                <x-admin.field name="reason" label="Customer's reason" required>
                    <x-admin.textarea name="reason" rows="2" required />
                </x-admin.field>
                <x-admin.field name="customer_notes" label="Customer notes">
                    <x-admin.textarea name="customer_notes" rows="2" />
                </x-admin.field>
                <x-admin.field name="requested_variant_id" label="Replacement variant (exchange only)">
                    <x-admin.select name="requested_variant_id" placeholder="Choose a variant"
                                    :options="$variants->mapWithKeys(fn ($variant) => [$variant->id => $variant->sku.' — '.$variant->product?->name])->all()" />
                </x-admin.field>
                <div class="flex justify-end gap-2 pt-1">
                    <x-admin.button type="button" variant="outline" size="sm"
                                    x-on:click="$dispatch('close-modal-exchange-create')">Cancel</x-admin.button>
                    <x-admin.button type="submit" size="sm" icon="rotate-ccw">Create request</x-admin.button>
                </div>
            </form>
        </x-admin.modal>
    @endcan
@endsection
