@extends('admin.layout')

@section('title', 'Refunds — Haarsingharr Admin')

<<<<<<< HEAD
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
=======
@php
    $colors = [
        'pending' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-blue-100 text-blue-800',
        'processing' => 'bg-indigo-100 text-indigo-800',
        'completed' => 'bg-emerald-100 text-emerald-800',
        'failed' => 'bg-rose-100 text-rose-800',
        'cancelled' => 'bg-slate-100 text-slate-700',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Refunds</h1>
            <p class="text-sm text-muted mt-1">Review and approve customer refunds.</p>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <select name="status">
            <option value="">All statuses</option>
            @foreach (array_keys($colors) as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
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
                        <th class="text-left p-3">Payment</th>
                        <th class="text-right p-3">Amount</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Requested</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($refunds as $refund)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                @if ($refund->order)
                                    <a href="{{ route('admin.orders.show', $refund->order) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $refund->order->number }}</a>
                                @else — @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $refund->payment ? ucfirst($refund->payment->provider) : '—' }}</td>
                            <td class="p-3 text-right font-medium">{{ number_format($refund->amount, 2) }}</td>
                            <td class="p-3 text-charcoal-soft">{{ ucfirst($refund->type) }}</td>
                            <td class="p-3"><span class="badge {{ $colors[$refund->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($refund->status) }}</span></td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $refund->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.refunds.show', $refund) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                                @if ($refund->canBeApproved())
                                    @can('refunds.approve')
                                    <form action="{{ route('admin.refunds.approve', $refund) }}" method="post" class="inline" onsubmit="return confirm('Approve and process this refund?')">
                                        @csrf
                                        <button class="text-xs text-emerald-700 hover:underline ml-2">Approve</button>
                                    </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($refunds->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No refunds found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $refunds->links() }}</div>
    </div>
>>>>>>> origin/main
@endsection
