@extends('admin.layout')

@section('title', 'Customer Report — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Customer Report</h1>
            <p class="text-sm text-muted mt-1">Acquisition, engagement and top spenders for the selected period.</p>
        </div>
    </div>

    @include('admin.reports._tabs')
    @include('admin.reports._period', ['type' => 'customers', 'compare' => $compare, 'compareFrom' => $compareFrom, 'compareTo' => $compareTo])

    @include('admin.reports._stats', ['stats' => [
        ['label' => 'Total Customers', 'value' => number_format($summary['total'])],
        ['label' => 'New (Period)', 'value' => number_format($summary['new']), 'key' => 'new'],
        ['label' => 'With Orders', 'value' => number_format($summary['with_orders']), 'key' => 'with_orders'],
        ['label' => 'Marketing Consent', 'value' => number_format($summary['marketing']), 'key' => 'marketing'],
    ], 'deltas' => $deltas])

    @unless (empty($daily))
        <div class="mb-6">
            <x-admin.chart
                :labels="$daily->pluck('day')->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('M j'))->all()"
                :series="[['label' => 'New customers', 'points' => $daily->pluck('total')->all()]]"
                type="bar"
                caption="New customers per day"
            />
        </div>
    @endunless

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- Daily signups --}}
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">New Customers per Day</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Date</th>
                            <th class="text-right p-3">New Customers</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($daily as $row)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3">{{ \Illuminate\Support\Carbon::parse($row->day)->format('D, M j, Y') }}</td>
                                <td class="p-3 text-right font-medium">{{ number_format($row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="p-8 text-center text-muted text-sm">No new customers in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top customers --}}
        <div class="card overflow-hidden">
            <div class="p-5 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Top Customers</h2>
                <p class="text-xs text-muted mt-0.5">By lifetime spend</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Customer</th>
                            <th class="text-right p-3">Orders</th>
                            <th class="text-right p-3">Spend</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($topCustomers as $customer)
                            <tr class="hover:bg-ivory-alt/30 transition-colors">
                                <td class="p-3">
                                    <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $customer->name }}</a>
                                    <div class="text-xs text-muted">{{ $customer->email }}</div>
                                </td>
                                <td class="p-3 text-right">{{ number_format($customer->orders_count) }}</td>
                                <td class="p-3 text-right font-medium">Rs. {{ number_format($customer->orders_sum_grand_total ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-8 text-center text-muted text-sm">No customers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
