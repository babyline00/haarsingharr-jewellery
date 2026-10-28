@extends('admin.layout')

@section('title', 'Coupons — Haarsingharr Admin')

@section('content')
<<<<<<< HEAD
    <x-admin.header title="Coupons" subtitle="Discount codes and promotions, including scheduled and archived codes.">
        <x-slot:actions>
            @can('coupons.create')
                <x-admin.button :href="route('admin.coupons.generate')" variant="outline" size="sm" icon="copy">Bulk generate</x-admin.button>
                <x-admin.button :href="route('admin.coupons.create')" size="sm" icon="plus">New Coupon</x-admin.button>
            @endcan
            <x-admin.button :href="route('admin.coupons.export', request()->query())" variant="ghost" size="sm" icon="download">Export</x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <x-admin.stat label="Active" :value="number_format($stats['active'])" icon="tag"
                      :href="route('admin.coupons.index', ['status' => 'active'])" />
        <x-admin.stat label="Scheduled" :value="number_format($stats['scheduled'])" icon="calendar"
                      :href="route('admin.coupons.index', ['status' => 'scheduled'])" />
        <x-admin.stat label="Expiring ≤ 7 days" :value="number_format($stats['expiring'])" icon="alert-triangle" />
        <x-admin.stat label="Lifetime discount given" :value="'Rs. '.number_format($stats['redeemed'])" icon="wallet" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[200px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="coupon-search" name="q" type="search" :value="$filters['q'] ?? ''" placeholder="Search by code" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-40">
            <x-admin.field name="status" label="Status">
                <x-admin.select id="coupon-status" name="status" :selected="$filters['status'] ?? ''"
                                placeholder="All statuses"
                                :options="['active' => 'Active', 'scheduled' => 'Scheduled', 'expired' => 'Expired', 'disabled' => 'Disabled']" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-48">
            <x-admin.field name="type" label="Type">
                <x-admin.select id="coupon-type" name="type" :selected="$filters['type'] ?? ''"
                                placeholder="All types"
                                :options="['percentage' => 'Percentage', 'fixed' => 'Fixed amount', 'free_shipping' => 'Free shipping', 'product_specific' => 'Product specific', 'category_specific' => 'Category specific']" />
            </x-admin.field>
        </div>

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.coupons.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.coupons.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>

            <select name="action" class="text-sm max-w-[180px]" aria-label="Bulk action">
                <option value="activate">Activate</option>
                <option value="disable">Disable</option>
                <option value="delete">Archive</option>
                <option value="export">Export selected</option>
            </select>

            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Coupons">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($coupons->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all coupons on this page">
                        </th>
                        <x-admin.th label="Code" />
                        <x-admin.th label="Type" />
                        <x-admin.th label="Restrictions" />
                        <x-admin.th label="Used" align="right" />
                        <x-admin.th label="Schedule" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>

                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($coupons as $coupon)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $coupon->id }}"
                                       x-model="selected" aria-label="Select coupon {{ $coupon->code }}">
                            </td>
                            <td class="p-3">
                                <a href="{{ route('admin.coupons.show', $coupon) }}"
                                   class="font-medium font-mono text-champagne-dark hover:underline">
                                    {{ $coupon->code }}
                                </a>
                                <span class="block text-xs text-muted">Rs. {{ number_format((float) ($coupon->redeemed_total ?? 0)) }} redeemed</span>
                            </td>
                            <td class="p-3 text-charcoal-soft">
                                {{ ucfirst(str_replace('_', ' ', $coupon->type)) }}
                                @if ($coupon->type === 'percentage')
                                    · {{ rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') }}%
                                @endif
                            </td>
                            <td class="p-3 text-xs text-muted">
                                @if ($coupon->min_order_value) <span class="block">Min spend {{ number_format($coupon->min_order_value, 0) }}</span> @endif
                                @if ($coupon->first_order_only) <span class="block">First order only</span> @endif
                                @if ($coupon->exclude_sale_items) <span class="block">Excludes sale</span> @endif
                                @if (! $coupon->min_order_value && ! $coupon->first_order_only && ! $coupon->exclude_sale_items) — @endif
                            </td>
                            <td class="p-3 text-right tabular-nums">
                                {{ $coupon->used_count }}{{ $coupon->usage_limit_total ? ' / ' . $coupon->usage_limit_total : '' }}
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">
                                {{ $coupon->starts_at?->format('M j, Y') ?? '—' }} → {{ $coupon->expires_at?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="p-3">
                                <x-admin.status-pill :status="$coupon->status" />
                            </td>
                            <td class="p-3">
                                <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                                    <a href="{{ route('admin.coupons.show', $coupon) }}"
                                       class="text-xs text-champagne-dark hover:underline">Usage</a>
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}"
                                       class="text-xs text-charcoal-soft hover:underline">Edit</a>
                                    @can('coupons.delete')
                                        <x-admin.confirm :action="route('admin.coupons.destroy', $coupon)"
                                                         method="DELETE"
                                                         title="Archive this coupon?"
                                                         message="The code will stop working immediately. Existing orders are not affected."
                                                         label="Archive"
                                                         confirm-label="Archive coupon" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-admin.empty icon="tag"
                                               title="No coupons yet"
                                               description="Create a discount code to run your first promotion.">
                                    @can('coupons.create')
                                        <x-slot:action>
                                            <x-admin.button :href="route('admin.coupons.create')" size="sm" icon="plus">New Coupon</x-admin.button>
                                        </x-slot:action>
                                    @endcan
                                </x-admin.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($coupons->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$coupons" />
                </div>
            @endif
        </x-admin.card>
    </form>
=======
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Coupons</h1>
            <p class="text-sm text-muted mt-1">Discount codes and promotions.</p>
        </div>
        @can('coupons.create')
        <a href="{{ route('admin.coupons.create') }}" class="btn-gold">New Coupon</a>
        @endcan
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search by code" class="flex-1 min-w-[200px]">
        <button type="submit" class="btn-outline text-sm">Filter</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Code</th>
                        <th class="text-left p-3">Type</th>
                        <th class="text-right p-3">Value</th>
                        <th class="text-right p-3">Used</th>
                        <th class="text-left p-3">Expires</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($coupons as $coupon)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="font-medium font-mono hover:text-champagne-dark transition-colors">{{ $coupon->code }}</a>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ ucfirst(str_replace('_', ' ', $coupon->type)) }}</td>
                            <td class="p-3 text-right font-medium">
                                {{ $coupon->type === 'percentage' ? rtrim(rtrim(number_format($coupon->value, 2), '0'), '.').'%' : 'Rs. '.number_format($coupon->value) }}
                            </td>
                            <td class="p-3 text-right">{{ $coupon->used_count }}{{ $coupon->usage_limit_total ? ' / '.$coupon->usage_limit_total : '' }}</td>
                            <td class="p-3 text-xs text-muted">{{ $coupon->expires_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="p-3">
                                @php $colors = ['active' => 'bg-emerald-100 text-emerald-800', 'scheduled' => 'bg-amber-100 text-amber-800', 'expired' => 'bg-slate-100 text-slate-700', 'disabled' => 'bg-rose-100 text-rose-800']; @endphp
                                <span class="badge {{ $colors[$coupon->status] ?? 'bg-slate-100 text-slate-700' }}">{{ ucfirst($coupon->status) }}</span>
                            </td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-xs text-champagne-dark hover:underline">Edit</a>
                                @can('coupons.delete')
                                <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="post" class="inline" onsubmit="return confirm('Archive this coupon?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-danger hover:underline ml-2">Archive</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    @if ($coupons->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No coupons yet.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $coupons->links() }}</div>
    </div>
>>>>>>> origin/main
@endsection
