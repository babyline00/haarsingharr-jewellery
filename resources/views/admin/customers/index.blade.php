@extends('admin.layout')

@section('title', 'Customers — Haarsingharr Admin')

@php
    $statusColors = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'suspended' => 'bg-amber-100 text-amber-800',
        'invited' => 'bg-blue-100 text-blue-800',
        'deactivated' => 'bg-rose-100 text-rose-800',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Customers</h1>
            <p class="text-sm text-muted mt-1">Registered customer accounts and their activity.</p>
        </div>
    </div>

    <form method="get" class="flex flex-wrap gap-3 mb-6">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or phone" class="flex-1 min-w-[200px]">
        <button type="submit" class="btn-outline text-sm">Search</button>
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Customer</th>
                        <th class="text-left p-3">Phone</th>
                        <th class="text-right p-3">Orders</th>
                        <th class="text-right p-3">Spend</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Joined</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($customers as $customer)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $customer->name ?: '—' }}</a>
                                <div class="text-xs text-muted">{{ $customer->email }}</div>
                            </td>
                            <td class="p-3 text-charcoal-soft">{{ $customer->phone ? $customer->phone_country.' '.$customer->phone : '—' }}</td>
                            <td class="p-3 text-right">{{ $customer->orders()->count() }}</td>
                            <td class="p-3 text-right">{{ number_format($customer->orders()->sum('grand_total'), 2) }}</td>
                            <td class="p-3"><span class="badge {{ $statusColors[$customer->status->value] ?? 'bg-slate-100 text-slate-700' }}">{{ $customer->status->label() }}</span></td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $customer->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right"><a href="{{ route('admin.customers.show', $customer) }}" class="text-xs text-champagne-dark hover:underline">View</a></td>
                        </tr>
                    @endforeach
                    @if ($customers->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No customers found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $customers->links() }}</div>
    </div>
@endsection
