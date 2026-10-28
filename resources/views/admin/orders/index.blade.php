@extends('admin.layout')

@section('title', 'Orders — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Orders" subtitle="Every order, newest first. Filter by status, payment, channel, date or value.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.orders.export', request()->query())" variant="outline" size="sm" icon="download">
                Export CSV
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Orders today" :value="number_format($stats['today'])" icon="calendar"
                       :href="route('admin.orders.index', ['date_from' => today()->toDateString(), 'date_to' => today()->toDateString()])" />
        <x-admin.stat label="Needs action" :value="number_format($stats['action'])" icon="clock"
                       :href="route('admin.orders.index', ['view' => 'needs_action'])" />
        <x-admin.stat label="Unpaid" :value="number_format($stats['unpaid'])" icon="alert-circle"
                       :href="route('admin.orders.index', ['view' => 'unpaid'])" />
        <x-admin.stat label="Paid this month" :value="'Rs. '.number_format($stats['month'])" icon="chart" />
    </div>

    <x-admin.tabs :items="$views" />

    <x-admin.filter-bar>
        <input type="hidden" name="view" value="{{ $filters['view'] ?? '' }}">

        <div class="flex-1 min-w-[220px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="order-search" name="q" type="search" :value="$filters['q'] ?? ''"
                               placeholder="Order #, customer, email, phone, tracking" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-44">
            <x-admin.field name="status" label="Order status">
                <x-admin.select id="order-status" name="status" :selected="$filters['status'] ?? ''"
                                placeholder="All statuses"
                                :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-44">
            <x-admin.field name="payment_status" label="Payment">
                <x-admin.select id="order-payment" name="payment_status" :selected="$filters['payment_status'] ?? ''"
                                placeholder="All payments"
                                :options="collect($paymentStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-40">
            <x-admin.field name="channel" label="Channel">
                <x-admin.select id="order-channel" name="channel" :selected="$filters['channel'] ?? ''"
                                placeholder="All channels"
                                :options="$channels->mapWithKeys(fn ($channel) => [$channel => ucfirst($channel)])->all()" />
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

        <div class="w-full sm:w-32">
            <x-admin.field name="min_total" label="Min Rs.">
                <x-admin.input name="min_total" type="number" min="0" step="1" :value="$filters['min_total'] ?? ''" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-32">
            <x-admin.field name="max_total" label="Max Rs.">
                <x-admin.input name="max_total" type="number" min="0" step="1" :value="$filters['max_total'] ?? ''" />
            </x-admin.field>
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.orders.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.orders.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>

            <select name="action" class="text-sm max-w-[180px]" aria-label="Bulk action">
                <option value="status">Change status</option>
                <option value="print">Print packing slips</option>
                <option value="export">Export selected</option>
                <option value="cancel">Cancel &amp; restock</option>
            </select>

            <select name="status" class="text-sm max-w-[180px]" aria-label="New status">
                <option value="">Choose status…</option>
                @foreach ($statuses as $status)
                    @if (! in_array($status, [\App\Enums\OrderStatus::Cancelled], true))
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endif
                @endforeach
            </select>

            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Orders">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($orders->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all orders on this page">
                        </th>
                        <x-admin.th label="Order" />
                        <x-admin.th label="Customer" />
                        <x-admin.th label="Channel" />
                        <x-admin.th label="Date" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Payment" />
                        <x-admin.th label="Total" align="right" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>

                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $order->id }}"
                                       x-model="selected" aria-label="Select order {{ $order->number }}">
                            </td>
                            <td class="p-3">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="font-medium text-champagne-dark hover:underline">
                                    {{ $order->number }}
                                </a>
                                <span class="block text-xs text-muted">{{ $order->items->sum('quantity') }} item(s)</span>
                            </td>
                            <td class="p-3">
                                <span class="text-sm">{{ $order->user?->name ?? $order->addresses->firstWhere('type', 'shipping')?->first_name ?? 'Guest' }}</span>
                                <span class="block text-xs text-muted">{{ $order->user?->email ?? $order->guest_email ?? $order->guest_phone }}</span>
                            </td>
                            <td class="p-3 text-xs capitalize text-muted">{{ $order->order_channel ?? 'web' }}</td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $order->created_at->format('M j, Y') }}<span class="block">{{ $order->created_at->format('g:i A') }}</span></td>
                            <td class="p-3">
                                <x-admin.status-pill :status="$order->status->value" :label="$order->status->label()" />
                            </td>
                            <td class="p-3">
                                <x-admin.status-pill :status="$order->payment_status->value" :label="$order->payment_status->label()" />
                            </td>
                            <td class="p-3 text-right font-medium"><x-admin.money :amount="$order->grand_total" /></td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="text-xs text-champagne-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-admin.empty icon="shopping-bag"
                                               title="No orders found"
                                               description="Try clearing a filter, or wait for the next checkout.">
                                    @if (array_filter($filters ?? []))
                                        <x-slot:action>
                                            <x-admin.button :href="route('admin.orders.index')" variant="outline" size="sm">Clear filters</x-admin.button>
                                        </x-slot:action>
                                    @endif
                                </x-admin.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($orders->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$orders" />
                </div>
            @endif
        </x-admin.card>
    </form>
@endsection
