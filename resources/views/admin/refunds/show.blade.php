@extends('admin.layout')

@section('title', 'Refund #' . $refund->id . ' — Haarsingharr Admin')

@php
    $payment = $refund->payment;
    $order = $refund->order ?? $payment?->order;
@endphp

@section('content')
    <x-admin.header :title="'Refund #'.$refund->id" :subtitle="$order ? 'Against order '.$order->number : 'Standalone refund'">
        <x-slot:actions>
            @if ($order)
                <x-admin.button :href="route('admin.orders.show', $order)" variant="outline" size="sm" icon="arrow-left">Order</x-admin.button>
            @endif
            @if ($refund->status === 'completed')
                <x-admin.button :href="route('admin.refunds.receipt', $refund)" variant="outline" size="sm" icon="receipt">
                    Receipt PDF
                </x-admin.button>
            @endif
            @can('refunds.approve')
                @if ($refund->canBeApproved())
                    <x-admin.button type="button" variant="primary" size="sm" icon="check"
                                    x-on:click="$dispatch('open-modal-approve-refund')">Approve</x-admin.button>
                @endif
            @endcan
            @can('refunds.update')
                @if (in_array($refund->status, ['approved', 'processing'], true))
                    <x-admin.button type="button" variant="primary" size="sm" icon="check-circle"
                                    x-on:click="$dispatch('open-modal-complete-refund')">Mark completed</x-admin.button>
                @endif
                @if (in_array($refund->status, ['pending', 'approved', 'processing'], true))
                    <x-admin.button type="button" variant="outline" size="sm" icon="ban"
                                    x-on:click="$dispatch('open-modal-reject-refund')">Reject</x-admin.button>
                @endif
            @endcan
        </x-slot:actions>

        <x-slot:meta>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <x-admin.status-pill :status="$refund->status" :label="ucwords(str_replace('_', ' ', $refund->status))" />
                <span class="badge badge-charcoal capitalize">{{ $refund->type }} refund</span>
                <x-admin.money :amount="$refund->amount" :decimals="2" class="font-medium" />
                <span class="text-muted">Requested {{ $refund->created_at->format('j M Y, g:i A') }}</span>
            </div>
        </x-slot:meta>
    </x-admin.header>

    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            <x-admin.card title="Refund request">
                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-widest text-muted">Reason</dt>
                        <dd class="mt-1 whitespace-pre-line">{{ $refund->reason }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Amount</dt>
                        <dd class="mt-1 font-medium"><x-admin.money :amount="$refund->amount" :decimals="2" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Type</dt>
                        <dd class="mt-1 capitalize">{{ $refund->type }} refund</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Payment</dt>
                        <dd class="mt-1">
                            @if ($payment)
                                <a href="{{ route('admin.payments.show', $payment) }}" class="text-champagne-dark hover:underline">
                                    {{ ucfirst(str_replace('_', ' ', $payment->provider)) }} · #{{ $payment->id }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Provider reference</dt>
                        <dd class="mt-1 break-all">{{ $refund->provider_ref ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Approved by</dt>
                        <dd class="mt-1">{{ $refund->approver?->name ?? '—' }} @if ($refund->approved_at)<span class="text-muted text-xs">on {{ $refund->approved_at->format('j M Y') }}</span>@endif</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Processed</dt>
                        <dd class="mt-1">{{ $refund->processed_at?->format('j M Y, g:i A') ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-widest text-muted">Notes</dt>
                        <dd class="mt-1 whitespace-pre-line text-charcoal-soft">{{ $refund->notes ?? 'No notes.' }}</dd>
                    </div>
                </dl>
            </x-admin.card>

            <x-admin.card title="Status timeline" subtitle="Approvals, rejections and settlement events.">
                <div class="space-y-4">
                    @forelse ($timeline as $event)
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-champagne mt-2 flex-shrink-0"></div>
                            <div class="min-w-0">
                                <div class="text-sm text-charcoal-soft">
                                    <span class="font-medium capitalize">{{ str_replace(['.', '_'], [' · ', ' '], $event->action) }}</span>
                                    @if ($event->actor)
                                        <span class="text-muted text-xs">by {{ $event->actor->name }}</span>
                                    @elseif ($event->actor_email)
                                        <span class="text-muted text-xs">by {{ $event->actor_email }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-muted">{{ $event->created_at->format('j M Y, g:i A') }}</div>
                                @if (! empty($event->after['status']))
                                    <div class="text-xs text-muted mt-0.5">Status set to {{ str_replace('_', ' ', $event->after['status']) }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No timeline events recorded yet.</p>
                    @endforelse
                </div>
            </x-admin.card>
        </div>

        <aside class="space-y-5">
            <x-admin.card title="Order">
                @if ($order)
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-muted">Order</span><a href="{{ route('admin.orders.show', $order) }}" class="text-champagne-dark hover:underline">{{ $order->number }}</a></div>
                        <div class="flex justify-between"><span class="text-muted">Order total</span><x-admin.money :amount="$order->grand_total" :decimals="2" /></div>
                        <div class="flex justify-between"><span class="text-muted">Paid</span><x-admin.money :amount="$order->paid_amount" :decimals="2" /></div>
                        <div class="flex justify-between"><span class="text-muted">Refunded</span><x-admin.money :amount="$order->refunded_amount" :decimals="2" /></div>
                        <div class="flex justify-between"><span class="text-muted">Payment status</span><x-admin.status-pill :status="$order->payment_status->value" :label="$order->payment_status->label()" /></div>
                    </div>
                @else
                    <p class="text-sm text-muted">The order no longer exists.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Stock">
                @if ($refund->restock_items)
                    <p class="text-sm text-charcoal-soft">This refund is set to return the pieces to sellable stock.</p>
                    <p class="text-xs text-muted mt-2">
                        @if ($refund->restocked_at)
                            Restocked {{ $refund->restocked_at->format('j M Y, g:i A') }}.
                        @else
                            Restock happens when the refund is completed.
                        @endif
                    </p>
                @else
                    <p class="text-sm text-muted">Stock will not be adjusted for this refund. Use the exchange workflow if the pieces are coming back.</p>
                @endif
            </x-admin.card>

            @if ($payment)
                <x-admin.card title="Payment">
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-muted">Provider</span><span class="capitalize">{{ str_replace('_', ' ', $payment->provider) }}</span></div>
                        <div class="flex justify-between"><span class="text-muted">Amount</span><x-admin.money :amount="$payment->amount" :decimals="2" /></div>
                        <div class="flex justify-between"><span class="text-muted">Captured</span><x-admin.money :amount="$payment->amount_captured" :decimals="2" /></div>
                        <div class="flex justify-between"><span class="text-muted">Refunded</span><x-admin.money :amount="$payment->amount_refunded" :decimals="2" /></div>
                        <div class="flex justify-between"><span class="text-muted">Status</span><x-admin.status-pill :status="$payment->status" :label="ucwords(str_replace('_', ' ', $payment->status))" /></div>
                    </div>
                </x-admin.card>
            @endif
        </aside>
    </div>

    @can('refunds.approve')
        @if ($refund->canBeApproved())
            <x-admin.modal name="approve-refund" title="Approve refund" max-width="max-w-md">
                <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}" class="space-y-3">
                    @csrf
                    <p class="text-sm text-muted">
                        COD refunds complete immediately. JazzCash and Easypaisa refunds move to processing until settlement is confirmed.
                        Manual providers complete with the reference below.
                    </p>
                    <x-admin.field name="reference" label="Manual reference">
                        <x-admin.input name="reference" placeholder="Bank / wallet reference" />
                    </x-admin.field>
                    <x-admin.field name="note" label="Approval note">
                        <x-admin.textarea name="note" rows="2" />
                    </x-admin.field>
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-approve-refund')">Cancel</x-admin.button>
                        <x-admin.button type="submit" size="sm" icon="check">Approve refund</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>
        @endif
    @endcan

    @can('refunds.update')
        @if (in_array($refund->status, ['approved', 'processing'], true))
            <x-admin.modal name="complete-refund" title="Mark refund completed" max-width="max-w-md">
                <form method="POST" action="{{ route('admin.refunds.complete', $refund) }}" class="space-y-3">
                    @csrf
                    <p class="text-sm text-muted">Confirm the money has reached the customer. Totals and statuses are recomputed from completed refunds.</p>
                    <x-admin.field name="reference" label="Settlement reference">
                        <x-admin.input name="reference" :value="$refund->provider_ref" />
                    </x-admin.field>
                    <x-admin.field name="note" label="Note">
                        <x-admin.textarea name="note" rows="2" />
                    </x-admin.field>
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-complete-refund')">Cancel</x-admin.button>
                        <x-admin.button type="submit" size="sm" icon="check-circle">Mark completed</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>
        @endif

        @if (in_array($refund->status, ['pending', 'approved', 'processing'], true))
            <x-admin.modal name="reject-refund" title="Reject refund" max-width="max-w-md">
                <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}" class="space-y-3">
                    @csrf
                    <p class="text-sm text-muted">The customer is not refunded. The reason is kept on the refund notes and audit log.</p>
                    <x-admin.field name="note" label="Reason" required>
                        <x-admin.textarea name="note" rows="3" required />
                    </x-admin.field>
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-reject-refund')">Cancel</x-admin.button>
                        <x-admin.button type="submit" variant="danger" size="sm">Reject refund</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>
        @endif
    @endcan
@endsection
