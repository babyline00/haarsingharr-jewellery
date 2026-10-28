@extends('admin.layout')

<<<<<<< HEAD
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
=======
@section('title', 'Request ' . $exchange->number . ' — Haarsingharr Admin')

@php
    $statusColors = [
        'pending' => 'bg-amber-100 text-amber-800',
        'reviewing' => 'bg-blue-100 text-blue-800',
        'approved' => 'bg-indigo-100 text-indigo-800',
        'rejected' => 'bg-rose-100 text-rose-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-slate-100 text-slate-700',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">{{ $exchange->number }}</h1>
            <p class="text-sm text-muted mt-1">{{ ucfirst($exchange->type) }} request · {{ $exchange->created_at?->format('M j, Y H:i') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $statusColors[$exchange->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($exchange->status) }}</span>
            <a href="{{ route('admin.exchanges.index') }}" class="btn-ghost text-sm">Back</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <div class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-4">Request Details</h2>
                <dl class="grid sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Order</dt>
                        <dd class="mt-1">
                            @if ($exchange->order)
                                <a href="{{ route('admin.orders.show', $exchange->order) }}" class="text-champagne-dark hover:underline font-medium">{{ $exchange->order->number }}</a>
                            @else — @endif
                        </dd>
                    </div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Item</dt><dd class="mt-1">{{ $exchange->orderItem?->product_name ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Quantity</dt><dd class="mt-1">{{ $exchange->quantity }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Requested variant</dt><dd class="mt-1">{{ $exchange->requestedVariant?->sku ?? '—' }}</dd></div>
                    <div><dt class="text-xs uppercase tracking-widest text-muted">Refund amount</dt><dd class="mt-1">{{ $exchange->refund_amount ? number_format($exchange->refund_amount, 2) : '—' }}</dd></div>
                    <div>
                        <dt class="text-xs uppercase tracking-widest text-muted">Linked refund</dt>
                        <dd class="mt-1">
                            @if ($exchange->refund)
                                <a href="{{ route('admin.refunds.show', $exchange->refund) }}" class="text-champagne-dark hover:underline">Refund #{{ $exchange->refund->id }}</a>
                            @else — @endif
                        </dd>
                    </div>
                </dl>

                <h3 class="font-serif text-base mt-5 mb-1">Customer reason</h3>
                <p class="text-sm whitespace-pre-line">{{ $exchange->reason }}</p>
                @if ($exchange->customer_notes)
                    <h3 class="font-serif text-base mt-4 mb-1">Customer notes</h3>
                    <p class="text-sm text-charcoal-soft whitespace-pre-line">{{ $exchange->customer_notes }}</p>
                @endif
            </div>

            <div class="card overflow-hidden">
                <div class="p-5 pb-3"><h2 class="font-serif text-lg">Order Items</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                            <tr>
                                <th class="text-left p-3">Product</th>
                                <th class="text-right p-3">Qty</th>
                                <th class="text-right p-3">Line total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-charcoal/5">
                            @foreach ($exchange->order?->items ?? [] as $item)
                                <tr class="{{ $exchange->order_item_id === $item->id ? 'bg-champagne/5' : '' }}">
                                    <td class="p-3">{{ $item->product_name }} @if ($item->variant_title) <span class="text-xs text-muted">{{ $item->variant_title }}</span> @endif</td>
                                    <td class="p-3 text-right">{{ $item->quantity }}</td>
                                    <td class="p-3 text-right">{{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Review</h2>
                @can('exchanges.update')
                <form action="{{ route('admin.exchanges.update', $exchange) }}" method="post" class="space-y-3">
                    @csrf @method('PUT')
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            @foreach (array_keys($statusColors) as $status)
                                <option value="{{ $status }}" @selected(old('status', $exchange->status) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="refund_amount">Refund amount</label>
                        <input type="number" step="0.01" min="0" id="refund_amount" name="refund_amount" value="{{ old('refund_amount', $exchange->refund_amount) }}">
                    </div>
                    <div>
                        <label for="admin_notes">Admin notes</label>
                        <textarea id="admin_notes" name="admin_notes" rows="4">{{ old('admin_notes', $exchange->admin_notes) }}</textarea>
                    </div>
                    <button type="submit" class="btn-gold w-full">Save</button>
                </form>
                @else
                    <p class="text-sm text-muted">{{ $exchange->admin_notes ?: 'No notes.' }}</p>
                @endcan
            </div>
        </aside>
    </div>
>>>>>>> origin/main
@endsection
