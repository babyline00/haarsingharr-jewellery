@extends('admin.layout')

@section('title', $exchange->number . ' — Haarsingharr Admin')

@php
    $order = $exchange->order;
    $item = $exchange->orderItem;
@endphp

@section('content')
    <x-admin.header :title="$exchange->number" :subtitle="'Created '.$exchange->created_at->format('j M Y, g:i A').' · '.($order?->number ?? 'No order')">
        <x-slot:actions>
            @if ($order)
                <x-admin.button :href="route('admin.orders.show', $order)" variant="outline" size="sm" icon="arrow-left">Order</x-admin.button>
            @endif
            @can('exchanges.update')
                @if (! $exchange->received_at && ! in_array($exchange->status, ['rejected', 'cancelled', 'completed'], true))
                    <x-admin.button type="button" variant="primary" size="sm" icon="package"
                                    x-on:click="$dispatch('open-modal-receive-exchange')">Mark received</x-admin.button>
                @endif
                @if (! in_array($exchange->status, ['rejected', 'cancelled', 'completed'], true))
                    <x-admin.button type="button" variant="outline" size="sm" icon="check"
                                    x-on:click="$dispatch('open-modal-complete-exchange')">Complete</x-admin.button>
                @endif
            @endcan
        </x-slot:actions>

        <x-slot:meta>
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <x-admin.status-pill :status="$exchange->status" :label="ucwords(str_replace('_', ' ', $exchange->status))" />
                <span class="badge badge-charcoal capitalize">{{ $exchange->type }}</span>
                @if ($exchange->restocked_at)
                    <span class="badge badge-success">Restocked {{ $exchange->restocked_at->format('j M') }}</span>
                @endif
            </div>
        </x-slot:meta>
    </x-admin.header>

    <div class="grid lg:grid-cols-3 gap-5">
        <div class="lg:col-span-2 space-y-5">
            <x-admin.card title="Request details">
                <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-widest text-muted">Reason</dt>
                        <dd class="mt-1 whitespace-pre-line">{{ $exchange->reason }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Item</dt>
                        <dd class="mt-1">
                            {{ $item?->product_name ?? 'Whole order' }}
                            @if ($item?->variant_title)<span class="text-muted"> — {{ $item->variant_title }}</span>@endif
                            @if ($item?->sku)<span class="block text-xs text-muted">{{ $item->sku }}</span>@endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Quantity</dt>
                        <dd class="mt-1">{{ $exchange->quantity }}</dd>
                    </div>
                    @if ($exchange->requestedVariant)
                        <div>
                            <dt class="text-xs uppercase tracking-widest text-muted">Replacement variant</dt>
                            <dd class="mt-1">{{ $exchange->requestedVariant->sku }} — {{ $exchange->requestedVariant->product?->name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Refund amount</dt>
                        <dd class="mt-1"><x-admin.money :amount="$exchange->refund_amount ?? 0" :decimals="2" /></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Return label</dt>
                        <dd class="mt-1">
                            @if ($exchange->return_label_url)
                                <a href="{{ $exchange->return_label_url }}" target="_blank" rel="noopener" class="text-champagne-dark hover:underline">Open label</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Received</dt>
                        <dd class="mt-1">{{ $exchange->received_at?->format('j M Y, g:i A') ?? 'Not received' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-widest text-muted">Customer notes</dt>
                        <dd class="mt-1 whitespace-pre-line text-charcoal-soft">{{ $exchange->customer_notes ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs uppercase tracking-widest text-muted">Internal notes</dt>
                        <dd class="mt-1 whitespace-pre-line text-charcoal-soft">{{ $exchange->admin_notes ?? 'No notes.' }}</dd>
                    </div>
                </dl>
            </x-admin.card>

            @can('exchanges.update')
                <x-admin.card title="Update request" subtitle="Status, internal notes, refund amount and return label.">
                    <form method="POST" action="{{ route('admin.exchanges.update', $exchange) }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid sm:grid-cols-2 gap-3">
                            <x-admin.field name="status" label="Status">
                                <x-admin.select name="status" :selected="$exchange->status"
                                                :options="['pending' => 'Pending', 'reviewing' => 'Reviewing', 'approved' => 'Approved', 'rejected' => 'Rejected', 'completed' => 'Completed', 'cancelled' => 'Cancelled']" />
                            </x-admin.field>
                            <x-admin.field name="refund_amount" label="Refund amount (PKR)">
                                <x-admin.input name="refund_amount" type="number" min="0" step="0.01" :value="$exchange->refund_amount" />
                            </x-admin.field>
                        </div>
                        <x-admin.field name="return_label_url" label="Return label URL">
                            <x-admin.input name="return_label_url" type="url" :value="$exchange->return_label_url" placeholder="https://…" />
                        </x-admin.field>
                        <x-admin.field name="admin_notes" label="Internal notes">
                            <x-admin.textarea name="admin_notes" rows="3" :value="$exchange->admin_notes" />
                        </x-admin.field>
                        <div class="flex justify-end">
                            <x-admin.button type="submit" size="sm" icon="save">Save changes</x-admin.button>
                        </div>
                    </form>
                </x-admin.card>
            @endcan

            <x-admin.card title="Timeline" subtitle="Audit history for this request.">
                <div class="space-y-4">
                    @forelse ($timeline as $event)
                        <div class="flex gap-3">
                            <div class="w-2 h-2 rounded-full bg-champagne mt-2 flex-shrink-0"></div>
                            <div>
                                <div class="text-sm text-charcoal-soft">
                                    <span class="font-medium capitalize">{{ str_replace(['.', '_'], [' · ', ' '], $event->action) }}</span>
                                    @if ($event->actor)
                                        <span class="text-muted text-xs">by {{ $event->actor->name }}</span>
                                    @endif
                                </div>
                                <div class="text-xs text-muted">{{ $event->created_at->format('j M Y, g:i A') }}</div>
                                @if (! empty($event->after['status']))
                                    <div class="text-xs text-muted mt-0.5">Status set to {{ str_replace('_', ' ', $event->after['status']) }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted">No timeline events yet.</p>
                    @endforelse
                </div>
            </x-admin.card>
        </div>

        <aside class="space-y-5">
            <x-admin.card title="Order">
                @if ($order)
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-muted">Number</span><a href="{{ route('admin.orders.show', $order) }}" class="text-champagne-dark hover:underline">{{ $order->number }}</a></div>
                        <div class="flex justify-between"><span class="text-muted">Total</span><x-admin.money :amount="$order->grand_total" :decimals="2" /></div>
                        <div class="flex justify-between"><span class="text-muted">Status</span><x-admin.status-pill :status="$order->status->value" :label="$order->status->label()" /></div>
                        <div class="flex justify-between"><span class="text-muted">Payment</span><x-admin.status-pill :status="$order->payment_status->value" :label="$order->payment_status->label()" /></div>
                    </div>
                @else
                    <p class="text-sm text-muted">Order no longer exists.</p>
                @endif
            </x-admin.card>

            <x-admin.card title="Receipt & stock">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-muted">Received</span><span>{{ $exchange->received_at ? 'Yes' : 'No' }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Restocked</span><span>{{ $exchange->restocked_at ? $exchange->restocked_at->format('j M Y') : 'No' }}</span></div>
                    <div class="flex justify-between"><span class="text-muted">Completed</span><span>{{ $exchange->completed_at?->format('j M Y') ?? 'No' }}</span></div>
                </div>
                @if ($exchange->refund)
                    <a href="{{ route('admin.refunds.show', $exchange->refund) }}" class="text-xs text-champagne-dark hover:underline mt-3 inline-block">View linked refund →</a>
                @endif
            </x-admin.card>
        </aside>
    </div>

    @can('exchanges.update')
        @if (! $exchange->received_at && ! in_array($exchange->status, ['rejected', 'cancelled', 'completed'], true))
            <x-admin.modal name="receive-exchange" title="Mark item received" max-width="max-w-md">
                <form method="POST" action="{{ route('admin.exchanges.receive', $exchange) }}" class="space-y-3">
                    @csrf
                    <p class="text-sm text-muted">Record that the returned piece has arrived. Restocking is idempotent and only happens once.</p>
                    <x-admin.checkbox name="restock" label="Return the piece to sellable stock now" checked />
                    <x-admin.field name="note" label="Note">
                        <x-admin.textarea name="note" rows="2" />
                    </x-admin.field>
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-receive-exchange')">Cancel</x-admin.button>
                        <x-admin.button type="submit" size="sm" icon="package">Mark received</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>
        @endif

        @if (! in_array($exchange->status, ['rejected', 'cancelled', 'completed'], true))
            <x-admin.modal name="complete-exchange" title="Complete this request?" max-width="max-w-md">
                <form method="POST" action="{{ route('admin.exchanges.complete', $exchange) }}" class="space-y-3">
                    @csrf
                    <p class="text-sm text-muted">This closes the request. Make sure the returned item has been received and any replacement or refund has been arranged.</p>
                    <div class="flex justify-end gap-2">
                        <x-admin.button type="button" variant="outline" size="sm"
                                        x-on:click="$dispatch('close-modal-complete-exchange')">Cancel</x-admin.button>
                        <x-admin.button type="submit" size="sm" icon="check">Complete request</x-admin.button>
                    </div>
                </form>
            </x-admin.modal>
        @endif
    @endcan
@endsection
