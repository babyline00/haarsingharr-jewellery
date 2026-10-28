@extends('admin.layout')

@section('title', 'Returns & Exchanges — Haarsingharr Admin')

<<<<<<< HEAD
@section('content')
    <x-admin.header title="Returns &amp; Exchanges" subtitle="After-sales requests, receipt of returned pieces and stock return.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.exchanges.export', request()->query())" variant="outline" size="sm" icon="download">
                Export CSV
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="New requests" :value="number_format($stats['pending'])" icon="inbox" />
        <x-admin.stat label="Approved" :value="number_format($stats['approved'])" icon="check-circle" />
        <x-admin.stat label="Awaiting receipt" :value="number_format($stats['awaiting_receipt'])" icon="package" />
        <x-admin.stat label="Completed" :value="number_format($stats['completed'])" icon="check" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input name="q" type="search" :value="$filters['q'] ?? ''" placeholder="Request # or order #" />
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
                                :options="collect($types)->mapWithKeys(fn ($type) => [$type => ucfirst($type)])->all()" />
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
            <x-admin.button :href="route('admin.exchanges.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <x-admin.card :padding="false">
        <x-admin.table caption="Returns and exchanges">
            <thead>
                <tr>
                    <x-admin.th label="Request" />
                    <x-admin.th label="Order" />
                    <x-admin.th label="Item" />
                    <x-admin.th label="Type" />
                    <x-admin.th label="Qty" align="center" />
                    <x-admin.th label="Status" />
                    <x-admin.th label="Requested" />
                    <x-admin.th label="Actions" align="right" />
                </tr>
            </thead>
            <tbody class="divide-y divide-charcoal/5">
                @forelse ($exchanges as $exchange)
                    <tr class="hover:bg-ivory-alt/30 transition-colors">
                        <td class="p-3">
                            <a href="{{ route('admin.exchanges.show', $exchange) }}" class="font-medium text-champagne-dark hover:underline">
                                {{ $exchange->number }}
                            </a>
                            @if ($exchange->restocked_at)
                                <span class="block text-xs text-success">Restocked</span>
                            @endif
                        </td>
                        <td class="p-3">
                            @if ($exchange->order)
                                <a href="{{ route('admin.orders.show', $exchange->order) }}" class="text-sm hover:underline">{{ $exchange->order->number }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="p-3 text-sm text-charcoal-soft max-w-[220px]">
                            {{ $exchange->orderItem?->product_name ?? 'Whole order' }}
                            @if ($exchange->requestedVariant)
                                <span class="block text-xs text-muted">→ {{ $exchange->requestedVariant->sku }}</span>
                            @endif
                        </td>
                        <td class="p-3 text-sm capitalize">{{ $exchange->type }}</td>
                        <td class="p-3 text-center text-sm">{{ $exchange->quantity }}</td>
                        <td class="p-3"><x-admin.status-pill :status="$exchange->status" :label="ucwords(str_replace('_', ' ', $exchange->status))" /></td>
                        <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $exchange->created_at->format('j M Y') }}<span class="block">{{ $exchange->created_at->format('g:i A') }}</span></td>
                        <td class="p-3 text-right">
                            <a href="{{ route('admin.exchanges.show', $exchange) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <x-admin.empty icon="rotate-ccw" title="No return requests"
                                           description="Create a return or exchange from an order detail page." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </x-admin.table>

        @if ($exchanges->total() > 0)
            <div class="px-4 border-t border-charcoal/5">
                <x-admin.pagination :paginator="$exchanges" />
            </div>
        @endif
    </x-admin.card>
=======
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
            <h1 class="font-serif text-2xl md:text-3xl">Returns &amp; Exchanges</h1>
            <p class="text-sm text-muted mt-1">Review customer return, exchange and refund requests.</p>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search request or order number" class="flex-1 min-w-[200px]">
        <select name="type">
            <option value="">All types</option>
            @foreach (['return', 'exchange', 'refund'] as $type)
                <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        <select name="status">
            <option value="">All statuses</option>
            @foreach (array_keys($statusColors) as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Request</th>
                        <th class="text-left p-3">Order</th>
                        <th class="text-left p-3">Item</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Qty</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($exchanges as $exchange)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 font-mono text-xs">{{ $exchange->number }}</td>
                            <td class="p-3">
                                @if ($exchange->order)
                                    <a href="{{ route('admin.orders.show', $exchange->order) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $exchange->order->number }}</a>
                                @else — @endif
                            </td>
                            <td class="p-3 text-charcoal-soft max-w-xs">{{ $exchange->orderItem?->product_name ?? '—' }}</td>
                            <td class="p-3">{{ ucfirst($exchange->type) }}</td>
                            <td class="p-3"><span class="badge {{ $statusColors[$exchange->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($exchange->status) }}</span></td>
                            <td class="p-3 text-right">{{ $exchange->quantity }}</td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $exchange->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right"><a href="{{ route('admin.exchanges.show', $exchange) }}" class="text-xs text-champagne-dark hover:underline">Review</a></td>
                        </tr>
                    @endforeach
                    @if ($exchanges->isEmpty())
                        <tr><td colspan="8" class="p-8 text-center text-muted text-sm">No requests found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $exchanges->links() }}</div>
    </div>
>>>>>>> origin/main
@endsection
