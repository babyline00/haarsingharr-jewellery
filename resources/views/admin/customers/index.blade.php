@extends('admin.layout')

@section('title', 'Customers — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Customers" subtitle="Every registered account, with lifetime value, segments and consent.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.customers.export', request()->query())" variant="outline" size="sm" icon="download">
                Export CSV
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <x-admin.stat label="Customers" :value="number_format($stats['total'])" icon="users" />
        <x-admin.stat label="New this month" :value="number_format($stats['new_month'])" icon="calendar"
                      :href="route('admin.customers.index', ['date_from' => now()->startOfMonth()->toDateString()])" />
        <x-admin.stat label="Active" :value="number_format($stats['active'])" icon="check-circle" />
        <x-admin.stat label="Blocked" :value="number_format($stats['blocked'])" icon="alert-triangle"
                      :href="route('admin.customers.index', ['segment' => 'blocked'])" />
        <x-admin.stat label="Marketing consent" :value="number_format($stats['with_consent'])" icon="star"
                      :href="route('admin.customers.index', ['consent' => 1])" />
    </div>

    <x-admin.tabs :items="$segments" />

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="customer-search" name="q" type="search" :value="$filters['q'] ?? ''"
                               placeholder="Name, email or phone" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-40">
            <x-admin.field name="status" label="Status">
                <x-admin.select id="customer-status" name="status" :selected="$filters['status'] ?? ''"
                                placeholder="All statuses"
                                :options="['active' => 'Active', 'suspended' => 'Suspended', 'deactivated' => 'Deactivated']" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-36">
            <x-admin.field name="consent" label="Consent">
                <x-admin.select id="customer-consent" name="consent" :selected="$filters['consent'] ?? ''"
                                placeholder="Any"
                                :options="['1' => 'Opted in', '0' => 'Opted out']" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-36">
            <x-admin.field name="date_from" label="Joined from">
                <x-admin.input name="date_from" type="date" :value="$filters['date_from'] ?? ''" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-36">
            <x-admin.field name="date_to" label="Joined to">
                <x-admin.input name="date_to" type="date" :value="$filters['date_to'] ?? ''" />
            </x-admin.field>
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.customers.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.customers.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>

            <select name="action" class="text-sm max-w-[200px]" aria-label="Bulk action">
                <option value="activate">Activate</option>
                <option value="suspend">Suspend</option>
                <option value="deactivate">Deactivate</option>
                <option value="consent_on">Grant marketing consent</option>
                <option value="consent_off">Withdraw marketing consent</option>
                <option value="export">Export selected</option>
            </select>

            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Customers">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($customers->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all customers on this page">
                        </th>
                        <x-admin.th label="Customer" />
                        <x-admin.th label="Phone" />
                        <x-admin.th label="Tags" />
                        <x-admin.th label="Orders" align="right" />
                        <x-admin.th label="Lifetime spend" align="right" />
                        <x-admin.th label="Consent" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Joined" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>

                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $customer->id }}"
                                       x-model="selected" aria-label="Select customer {{ $customer->email }}">
                            </td>
                            <td class="p-3">
                                <a href="{{ route('admin.customers.show', $customer) }}"
                                   class="font-medium text-champagne-dark hover:underline">
                                    {{ $customer->name ?: '—' }}
                                </a>
                                <span class="block text-xs text-muted">{{ $customer->email }}</span>
                            </td>
                            <td class="p-3 text-xs text-charcoal-soft whitespace-nowrap">
                                {{ $customer->phone ? $customer->phone_country.' '.$customer->phone : '—' }}
                            </td>
                            <td class="p-3">
                                @if ($customer->tags)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (array_slice($customer->tags, 0, 3) as $tag)
                                            <span class="badge bg-ivory-alt text-charcoal-soft">{{ $tag }}</span>
                                        @endforeach
                                        @if (count($customer->tags) > 3)
                                            <span class="text-xs text-muted">+{{ count($customer->tags) - 3 }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-3 text-right tabular-nums">{{ number_format($customer->orders_count) }}</td>
                            <td class="p-3 text-right font-medium"><x-admin.money :amount="$customer->lifetime_spend ?? 0" /></td>
                            <td class="p-3">
                                @if ($customer->marketing_consent)
                                    <span class="badge bg-emerald-100 text-emerald-800">Opted in</span>
                                @else
                                    <span class="text-xs text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <x-admin.status-pill :status="$customer->status->value" :label="$customer->status->label()" />
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $customer->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.customers.show', $customer) }}"
                                   class="text-xs text-champagne-dark hover:underline">View</a>
                                @can('customers.update')
                                    @if ($customer->status->value === 'active')
                                        <form action="{{ route('admin.customers.impersonate', $customer) }}" method="post" class="inline">
                                            @csrf
                                            <button class="text-xs text-charcoal-soft hover:underline ml-2">Open in store</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10">
                                <x-admin.empty icon="users"
                                               title="No customers found"
                                               description="Try clearing a filter or widening the date range.">
                                    @if (array_filter($filters ?? []))
                                        <x-slot:action>
                                            <x-admin.button :href="route('admin.customers.index')" variant="outline" size="sm">Clear filters</x-admin.button>
                                        </x-slot:action>
                                    @endif
                                </x-admin.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($customers->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$customers" />
                </div>
            @endif
        </x-admin.card>
    </form>
@endsection
