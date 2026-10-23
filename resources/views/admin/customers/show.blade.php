@extends('admin.layout')

@section('title', ($customer->name ?: $customer->email) . ' — Haarsingharr Admin')

@php
    $statusColors = [
        'active' => 'bg-emerald-100 text-emerald-800',
        'suspended' => 'bg-amber-100 text-amber-800',
        'invited' => 'bg-blue-100 text-blue-800',
        'deactivated' => 'bg-rose-100 text-rose-800',
    ];
    $orders = $customer->orders()->latest()->limit(10)->get();
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">{{ $customer->name ?: 'Customer' }}</h1>
            <p class="text-sm text-muted mt-1">{{ $customer->email }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $statusColors[$customer->status->value] ?? 'bg-slate-100 text-slate-700' }}">{{ $customer->status->label() }}</span>
            <a href="{{ route('admin.customers.index') }}" class="btn-ghost text-sm">Back</a>
        </div>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="card p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Orders</div>
            <div class="font-serif text-2xl mt-1">{{ $customer->orders()->count() }}</div>
        </div>
        <div class="card p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Lifetime spend</div>
            <div class="font-serif text-2xl mt-1">{{ number_format($customer->orders()->sum('grand_total'), 2) }}</div>
        </div>
        <div class="card p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Reviews</div>
            <div class="font-serif text-2xl mt-1">{{ $customer->reviews->count() }}</div>
        </div>
        <div class="card p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Wishlist items</div>
            <div class="font-serif text-2xl mt-1">{{ $customer->wishlist?->items->count() ?? 0 }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <div class="space-y-4">
            <div class="card overflow-hidden">
                <div class="p-5 pb-3 flex items-center justify-between">
                    <h2 class="font-serif text-lg">Recent Orders</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                            <tr>
                                <th class="text-left p-3">Number</th>
                                <th class="text-left p-3">Status</th>
                                <th class="text-right p-3">Total</th>
                                <th class="text-left p-3">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-charcoal/5">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="p-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-champagne-dark hover:underline">{{ $order->number }}</a></td>
                                    <td class="p-3"><span class="badge {{ $order->status->color() }}">{{ $order->status->label() }}</span></td>
                                    <td class="p-3 text-right">{{ number_format($order->grand_total, 2) }}</td>
                                    <td class="p-3 text-xs text-muted">{{ $order->created_at?->format('M j, Y') }}</td>
                                </tr>
                            @endforeach
                            @if ($orders->isEmpty())
                                <tr><td colspan="4" class="p-6 text-center text-muted text-sm">No orders yet.</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Addresses</h2>
                @if ($customer->addresses->isNotEmpty())
                    <div class="grid md:grid-cols-2 gap-4">
                        @foreach ($customer->addresses as $address)
                            <div class="border border-charcoal/10 rounded-md p-3 text-sm">
                                <div class="font-medium">{{ $address->label ?: 'Address' }}
                                    @if ($address->is_default_shipping) <span class="badge bg-ivory-alt text-charcoal-soft">Default shipping</span> @endif
                                </div>
                                <div class="text-charcoal-soft mt-1">{{ $address->fullName() }}</div>
                                <div class="text-muted text-xs mt-1">{{ $address->fullAddress() }}</div>
                                <div class="text-muted text-xs">{{ $address->fullPhone() }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-muted">No addresses on file.</p>
                @endif
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Manage</h2>
                @can('customers.update')
                <form action="{{ route('admin.customers.update', $customer) }}" method="post" class="space-y-3">
                    @csrf @method('PUT')
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            @foreach (['active', 'suspended', 'deactivated'] as $status)
                                <option value="{{ $status }}" @selected(old('status', $customer->status->value) === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent', $customer->marketing_consent))>
                        Marketing consent
                    </label>
                    <div>
                        <label for="internal_notes">Internal notes</label>
                        <textarea id="internal_notes" name="internal_notes" rows="3">{{ old('internal_notes', $customer->internal_notes) }}</textarea>
                    </div>
                    <button type="submit" class="btn-gold w-full">Save</button>
                </form>
                @endcan
            </div>

            <div class="card p-5 text-sm space-y-2">
                <h2 class="font-serif text-lg mb-2">Profile</h2>
                <div class="flex justify-between"><span class="text-muted">Type</span><span>{{ $customer->type->label() }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Phone</span><span>{{ $customer->phone ? $customer->phone_country.' '.$customer->phone : '—' }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Verified</span><span>{{ $customer->email_verified_at ? 'Yes' : 'No' }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Last login</span><span>{{ $customer->last_login_at?->format('M j, Y') ?? '—' }}</span></div>
                <div class="flex justify-between"><span class="text-muted">Joined</span><span>{{ $customer->created_at?->format('M j, Y') }}</span></div>
            </div>
        </aside>
    </div>
@endsection
