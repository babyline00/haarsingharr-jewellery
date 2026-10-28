@extends('admin.layout')

@section('title', 'Payments — Haarsingharr Admin')

@section('content')
<<<<<<< HEAD
    <x-admin.header title="Payments" subtitle="Gateway transactions, manual captures and reconciliation.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.payments.export', request()->query())" variant="outline" size="sm" icon="download">
                Export CSV
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Captured" :value="'Rs. '.number_format($stats['captured'])" icon="wallet" />
        <x-admin.stat label="Refunded" :value="'Rs. '.number_format($stats['refunded'])" icon="rotate-ccw" />
        <x-admin.stat label="Awaiting" :value="number_format($stats['pending'])" icon="clock" />
        <x-admin.stat label="Failed" :value="number_format($stats['failed'])" icon="alert-triangle" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input name="q" type="search" :value="$filters['q'] ?? ''"
                               placeholder="Order #, transaction ref, provider payment id" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-44">
            <x-admin.field name="status" label="Status">
                <x-admin.select name="status" :selected="$filters['status'] ?? ''" placeholder="All statuses"
                                :options="collect($statuses)->mapWithKeys(fn ($status) => [$status => ucwords(str_replace('_', ' ', $status))])->all()" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-44">
            <x-admin.field name="provider" label="Provider">
                <x-admin.select name="provider" :selected="$filters['provider'] ?? ''" placeholder="All providers"
                                :options="$providers->mapWithKeys(fn ($provider) => [$provider => ucfirst(str_replace('_', ' ', $provider))])->all()" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-36">
            <x-admin.field name="date_from" label="From">
                <x-admin.input name="date_from" type="date" :value="$filters['date_from'] ?? ''" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-36">
            <x-admin.field name="date_to" label="To">
                <x-admin.input name="date_to" type="date" :value="$filters['date_to'] ?? ''" />
            </x-admin.field>
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.payments.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <x-admin.card :padding="false">
        <x-admin.table caption="Payments">
            <thead>
                <tr>
                    <x-admin.th label="Order" />
                    <x-admin.th label="Provider" />
                    <x-admin.th label="Status" />
                    <x-admin.th label="Amount" align="right" />
                    <x-admin.th label="Captured" align="right" />
                    <x-admin.th label="Refunded" align="right" />
                    <x-admin.th label="Reference" />
                    <x-admin.th label="Date" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-charcoal/5">
                @forelse ($payments as $payment)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3">
                            @if ($payment->order)
                                <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-medium text-champagne-dark hover:underline">
                                    {{ $payment->order->number }}
                                </a>
                                <span class="block text-xs text-muted">{{ $payment->order->user?->email ?? $payment->order->guest_email }}</span>
                            @else
                                <span class="text-muted">Deleted order</span>
                            @endif
                        </td>
                        <td class="p-3 text-sm capitalize">{{ str_replace('_', ' ', $payment->provider) }}<span class="block text-xs text-muted">{{ $payment->method }}</span></td>
                        <td class="p-3"><x-admin.status-pill :status="$payment->status" :label="ucwords(str_replace('_', ' ', $payment->status))" /></td>
                        <td class="p-3 text-right text-sm"><x-admin.money :amount="$payment->amount" :decimals="2" /></td>
                        <td class="p-3 text-right text-sm text-muted"><x-admin.money :amount="$payment->amount_captured" :decimals="2" /></td>
                        <td class="p-3 text-right text-sm text-muted"><x-admin.money :amount="$payment->amount_refunded" :decimals="2" /></td>
                        <td class="p-3 text-xs text-muted max-w-[180px] truncate">{{ $payment->provider_transaction_ref ?? $payment->provider_payment_id ?? '—' }}</td>
                        <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $payment->created_at->format('j M Y') }}<span class="block">{{ $payment->created_at->format('g:i A') }}</span></td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin.payments.show', $payment) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <x-admin.empty icon="wallet" title="No payments found"
                                           description="Payments appear here when a customer starts a checkout or staff records a manual payment." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>

        @if ($payments->total() > 0)
            <div class="px-4 border-t border-charcoal/5">
                <x-admin.pagination :paginator="$payments" />
            </div>
        @endif
    </x-admin.card>
=======
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Payments</h1>
            <p class="text-sm text-muted mt-1">Gateway transactions and their current state.</p>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search transaction or payment id" class="flex-1 min-w-[200px]">
        <select name="status">
            <option value="">All statuses</option>
            @foreach (['pending', 'initiated', 'authorized', 'captured', 'failed', 'cancelled', 'refunded', 'partially_refunded'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Order</th>
                        <th class="text-left p-3">Provider</th>
                        <th class="text-left p-3">Reference</th>
                        <th class="text-right p-3">Amount</th>
                        <th class="text-right p-3">Captured</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-right p-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($payments as $payment)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                @if ($payment->order)
                                    <a href="{{ route('admin.orders.show', $payment->order) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $payment->order->number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ ucfirst($payment->provider) }} <span class="text-xs text-muted">/ {{ $payment->method }}</span></td>
                            <td class="p-3 text-xs font-mono text-muted">{{ $payment->provider_transaction_ref ?: $payment->provider_payment_id ?: '—' }}</td>
                            <td class="p-3 text-right font-medium">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                            <td class="p-3 text-right text-charcoal-soft">{{ number_format($payment->amount_captured, 2) }}</td>
                            <td class="p-3">
                                @php
                                    $colors = [
                                        'pending' => 'bg-slate-100 text-slate-700',
                                        'initiated' => 'bg-blue-100 text-blue-800',
                                        'authorized' => 'bg-indigo-100 text-indigo-800',
                                        'captured' => 'bg-emerald-100 text-emerald-800',
                                        'failed' => 'bg-rose-100 text-rose-800',
                                        'cancelled' => 'bg-slate-100 text-slate-700',
                                        'refunded' => 'bg-blue-100 text-blue-800',
                                        'partially_refunded' => 'bg-orange-100 text-orange-800',
                                    ];
                                @endphp
                                <span class="badge {{ $colors[$payment->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucwords(str_replace('_', ' ', $payment->status)) }}</span>
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $payment->created_at?->format('M j, Y H:i') }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach
                    @if ($payments->isEmpty())
                        <tr><td colspan="8" class="p-8 text-center text-muted text-sm">No payments found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $payments->links() }}</div>
    </div>
>>>>>>> origin/main
@endsection
