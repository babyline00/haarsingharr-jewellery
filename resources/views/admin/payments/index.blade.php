@extends('admin.layout')

@section('title', 'Payments — Haarsingharr Admin')

@section('content')
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
@endsection
