@extends('admin.layout')

@section('title', 'Refunds — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Refunds" subtitle="Requests, approvals and settled money returns.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.refunds.export', request()->query())" variant="outline" size="sm" icon="download">
                Export CSV
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Awaiting approval" :value="number_format($stats['pending'])" icon="clock" />
        <x-admin.stat label="In settlement" :value="number_format($stats['processing'])" icon="refresh" />
        <x-admin.stat label="Completed value" :value="'Rs. '.number_format($stats['completed'])" icon="rotate-ccw" />
        <x-admin.stat label="Completed refunds" :value="number_format($stats['refunded_total'])" icon="receipt" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input name="q" type="search" :value="$filters['q'] ?? ''"
                               placeholder="Order #, provider ref, transaction id" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-44">
            <x-admin.field name="status" label="Status">
                <x-admin.select name="status" :selected="$filters['status'] ?? ''" placeholder="All statuses"
                                :options="collect($statuses)->mapWithKeys(fn ($status) => [$status => ucwords(str_replace('_', ' ', $status))])->all()" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-40">
            <x-admin.field name="type" label="Type">
                <x-admin.select name="type" :selected="$filters['type'] ?? ''" placeholder="All types"
                                :options="['full' => 'Full', 'partial' => 'Partial']" />
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
            <x-admin.button :href="route('admin.refunds.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <x-admin.card :padding="false">
        <x-admin.table caption="Refunds">
            <thead>
                <tr>
                    <x-admin.th label="Refund" />
                    <x-admin.th label="Order" />
                    <x-admin.th label="Payment" />
                    <x-admin.th label="Amount" align="right" />
                    <x-admin.th label="Type" />
                    <x-admin.th label="Status" />
                    <x-admin.th label="Requested" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-charcoal/5">
                @forelse ($refunds as $refund)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3">
                            <a href="{{ route('admin.refunds.show', $refund) }}" class="font-medium text-champagne-dark hover:underline">
                                #{{ $refund->id }}
                            </a>
                            @if ($refund->restock_items)
                                <span class="block text-xs text-muted">Restock requested</span>
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($refund->order)
                                <a href="{{ route('admin.orders.show', $refund->order) }}" class="text-sm hover:underline">{{ $refund->order->number }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="p-3 text-sm capitalize">{{ $refund->payment ? str_replace('_', ' ', $refund->payment->provider) : '—' }}</td>
                        <td class="p-3 text-right font-medium"><x-admin.money :amount="$refund->amount" :decimals="2" /></td>
                        <td class="p-3 text-sm capitalize">{{ $refund->type }}</td>
                        <td class="p-3"><x-admin.status-pill :status="$refund->status" :label="ucwords(str_replace('_', ' ', $refund->status))" /></td>
                        <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $refund->created_at->format('j M Y') }}<span class="block">{{ $refund->created_at->format('g:i A') }}</span></td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin.refunds.show', $refund) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-admin.empty icon="rotate-ccw" title="No refunds found"
                                           description="Refunds are requested from an order's payment or payment detail page." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>

        @if ($refunds->total() > 0)
            <div class="px-4 border-t border-charcoal/5">
                <x-admin.pagination :paginator="$refunds" />
            </div>
        @endif
    </x-admin.card>
@endsection
