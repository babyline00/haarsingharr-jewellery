@extends('admin.layout')

@section('title', 'Payment #' . $payment->id . ' — Haarsingharr Admin')

@php
    $order = $payment->order;
    $canCapture = ! in_array($payment->status, ['captured', 'refunded', 'partially_refunded'], true);
@endphp

@section('content')
    <x-admin.header :title="'Payment #'.$payment->id" :subtitle="ucfirst(str_replace('_', ' ', $payment->provider)).' · '.$payment->currency.' '.number_format((float) $payment->amount, 2)">
        <x-slot:actions>
            @if ($order)
                <x-admin.button :href="route('admin.orders.show', $order)" variant="outline" size="sm" icon="arrow-left">
                    Back to order
                </x-admin.button>
            @endif
            @if ($canCapture)
                <x-admin.button type="button" variant="primary" size="sm" icon="check"
                                x-on:click="$dispatch('open-modal-capture-payment')">
                    Mark captured
                </x-admin.button>
                <x-admin.button type="button" variant="outline" size="sm" icon="ban"
                                x-on:click="$dispatch('open-modal-fail-payment')">
                    Mark failed
                </x-admin.button>
            @endif
        </x-slot:actions>

        <x-slot:meta>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <x-admin.status-pill :status="$payment->status" :label="ucwords(str_replace('_', ' ', $payment->status))" />
                <span class="text-muted">Initiated {{ $payment->created_at->format('j M Y, g:i A') }}</span>
            </div>
        </x-slot:meta>
    </x-admin.header>

    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <x-admin.stat label="Amount" :value="'Rs. '.number_format((float) $payment->amount)" />
                <x-admin.stat label="Captured" :value="'Rs. '.number_format((float) $payment->amount_captured)" />
                <x-admin.stat label="Refunded" :value="'Rs. '.number_format((float) $payment->amount_refunded)" />
                <x-admin.stat label="Refundable" :value="'Rs. '.number_format($refundable)" />
            </div>

            <x-admin.card title="Payment details">
                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Provider</dt><dd class="mt-0.5 capitalize">{{ str_replace('_', ' ', $payment->provider) }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Method</dt><dd class="mt-0.5">{{ $payment->method ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Transaction reference</dt><dd class="mt-0.5 break-all">{{ $payment->provider_transaction_ref ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Provider payment ID</dt><dd class="mt-0.5 break-all">{{ $payment->provider_payment_id ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Provider status</dt><dd class="mt-0.5">{{ $payment->provider_status ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Currency</dt><dd class="mt-0.5">{{ $payment->currency }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Initiated</dt><dd class="mt-0.5">{{ $payment->initiated_at?->format('j M Y, g:i A') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Captured</dt><dd class="mt-0.5">{{ $payment->captured_at?->format('j M Y, g:i A') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Failed</dt><dd class="mt-0.5">{{ $payment->failed_at?->format('j M Y, g:i A') ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Order</dt><dd class="mt-0.5">@if ($order)<a href="{{ route('admin.orders.show', $order) }}" class="text-champagne-dark hover:underline">{{ $order->number }}</a>@else — @endif</dd></div>
                </dl>
            </x-admin.card>

            <x-admin.card title="Provider event log" subtitle="Webhook and verification events with their raw payloads." :padding="false">
                @if ($payment->events->isEmpty())
                    <div class="p-5 text-sm text-muted">No provider events recorded.</div>
                @else
                    <div class="divide-y divide-charcoal/5">
                        @foreach ($payment->events as $event)
                            <div class="p-4">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div>
                                        <div class="text-sm font-medium capitalize">{{ str_replace('_', ' ', $event->type) }}</div>
                                        <div class="text-xs text-muted">
                                            {{ $event->created_at->format('j M Y, g:i A') }}
                                            @if ($event->provider_event_id) · {{ $event->provider_event_id }} @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if ($event->is_verified)
                                            <span class="badge badge-success">Verified</span>
                                        @endif
                                        <x-admin.status-pill :status="$event->status" :label="ucwords(str_replace('_', ' ', $event->status ?? 'event'))" />
                                    </div>
                                </div>
                                @if ($event->note)
                                    <p class="text-sm text-muted mt-2">{{ $event->note }}</p>
                                @endif
                                @if ($event->payload)
                                    <details class="mt-3">
                                        <summary class="text-xs text-champagne-dark cursor-pointer hover:underline">View payload</summary>
                                        <pre class="mt-2 p-3 rounded-md bg-charcoal text-ivory text-xs overflow-x-auto">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>
        </div>

        <aside class="space-y-5">
            <x-admin.card title="Reconciliation" subtitle="How this payment balances.">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-muted">Expected</span><x-admin.money :amount="$payment->amount" :decimals="2" /></div>
                    <div class="flex justify-between"><span class="text-muted">Captured</span><x-admin.money :amount="$payment->amount_captured" :decimals="2" /></div>
                    <div class="flex justify-between"><span class="text-muted">Refunded</span><x-admin.money :amount="$payment->amount_refunded" :decimals="2" /></div>
                    <div class="flex justify-between border-t border-charcoal/5 pt-2 font-medium">
                        <span>Net retained</span>
                        <x-admin.money :amount="(float) $payment->amount_captured - (float) $payment->amount_refunded" :decimals="2" />
                    </div>
                    @php
                        $variance = (float) $payment->amount_captured - ((float) $payment->amount - (float) $payment->amount_refunded);
                    @endphp
                    @if (abs($variance) > 0.009)
                        <div class="text-xs text-danger">Variance: Rs. {{ number_format($variance, 2) }}</div>
                    @else
                        <div class="text-xs text-success">Balanced</div>
                    @endif
                </div>
            </x-admin.card>

            <x-admin.card title="Refunds" :padding="false">
                <x-slot:actions>
                    @can('refunds.create')
                        @if ($refundable > 0)
                            <button type="button" class="text-xs text-champagne-dark hover:underline"
                                    x-on:click="$dispatch('open-modal-refund-payment')">Request</button>
                        @endif
                    @endcan
                </x-slot:actions>

                @if ($payment->refunds->isEmpty())
                    <div class="p-5 text-sm text-muted">No refunds against this payment.</div>
                @else
                    <div class="divide-y divide-charcoal/5">
                        @foreach ($payment->refunds as $refund)
                            <a href="{{ route('admin.refunds.show', $refund) }}" class="flex items-center justify-between gap-2 p-4 hover:bg-ivory-alt/30 transition-colors">
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
        </aside>
    </div>

    @if ($canCapture)
        <x-admin.modal name="capture-payment" title="Mark payment captured" max-width="max-w-md">
            <form method="POST" action="{{ route('admin.payments.capture', $payment) }}" class="space-y-3">
                @csrf
                <p class="text-sm text-muted">This records the full payment amount as captured and updates the order's paid balance.</p>
                <x-admin.field name="reference" label="Transaction reference">
                    <x-admin.input name="reference" :value="$payment->provider_transaction_ref" />
                </x-admin.field>
                <x-admin.field name="note" label="Note">
                    <x-admin.textarea name="note" rows="2" />
                </x-admin.field>
                <div class="flex justify-end gap-2">
                    <x-admin.button type="button" variant="outline" size="sm"
                                    x-on:click="$dispatch('close-modal-capture-payment')">Cancel</x-admin.button>
                    <x-admin.button type="submit" size="sm" icon="check">Mark captured</x-admin.button>
                </div>
            </form>
        </x-admin.modal>

        <x-admin.modal name="fail-payment" title="Mark payment failed" max-width="max-w-md">
            <form method="POST" action="{{ route('admin.payments.fail', $payment) }}" class="space-y-3">
                @csrf
                <p class="text-sm text-muted">The reason is recorded on the payment event log for reconciliation.</p>
                <x-admin.field name="reason" label="Reason" required>
                    <x-admin.textarea name="reason" rows="3" required />
                </x-admin.field>
                <div class="flex justify-end gap-2">
                    <x-admin.button type="button" variant="outline" size="sm"
                                    x-on:click="$dispatch('close-modal-fail-payment')">Cancel</x-admin.button>
                    <x-admin.button type="submit" variant="danger" size="sm">Mark failed</x-admin.button>
                </div>
            </form>
        </x-admin.modal>
    @endif

    @can('refunds.create')
        @if ($refundable > 0)
            <x-admin.modal name="refund-payment" title="Request refund" max-width="max-w-lg">
                <form method="POST" action="{{ route('admin.refunds.store') }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                    <div class="grid sm:grid-cols-2 gap-3">
                        <x-admin.field name="amount" label="Amount (PKR)" required>
                            <x-admin.input name="amount" type="number" min="0.01" step="0.01"
                                           :value="number_format($refundable, 2, '.', '')" required />
                        </x-admin.field>
                        <x-admin.field name="type" label="Type" required>
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
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-refund-payment')">Cancel</x-admin.button>
                        <x-admin.button type="submit" size="sm" icon="rotate-ccw">Request refund</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>
        @endif
    @endcan
@endsection
