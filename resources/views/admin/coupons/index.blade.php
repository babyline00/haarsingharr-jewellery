@extends('admin.layout')

@section('title', 'Coupons — Haarsingharr Admin')

@section('content')
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
@endsection
