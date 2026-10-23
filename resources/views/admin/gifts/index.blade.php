@extends('admin.layout')

@section('title', 'Gift Popup — Haarsingharr Admin')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Gift Popup</h1>
            <p class="text-sm text-muted mt-1">Impressions, claims, unique coupons and giveaways — last {{ $days }} days.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.settings.index') }}#settings-marketing" class="btn-outline text-sm">Popup Settings</a>
            <a href="{{ route('admin.giveaways.index') }}" class="btn-gold text-sm">Giveaways</a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Impressions</div>
            <div class="font-serif text-3xl mt-1">{{ number_format($stats['impressions']) }}</div>
            <div class="text-xs text-muted mt-1">Popup opened</div>
        </div>
        <div class="card p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Claims</div>
            <div class="font-serif text-3xl mt-1">{{ number_format($stats['claims']) }}</div>
            <div class="text-xs text-muted mt-1">{{ $stats['all_time'] }} all-time</div>
        </div>
        <div class="card p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Conversion</div>
            <div class="font-serif text-3xl mt-1">{{ $stats['conversion'] }}%</div>
            <div class="text-xs text-muted mt-1">Claims ÷ impressions</div>
        </div>
        <div class="card p-5">
            <div class="text-xs uppercase tracking-widest text-muted">Redeemed</div>
            <div class="font-serif text-3xl mt-1">{{ number_format($stats['redeemed']) }}</div>
            <div class="text-xs text-muted mt-1">{{ number_format($stats['issued']) }} unique coupons issued</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-6">
        <div class="card p-5 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-serif text-lg">Last 14 days</h2>
                <div class="flex items-center gap-4 text-xs text-muted">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-champagne"></span> Impressions</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-charcoal"></span> Claims</span>
                </div>
            </div>
            <div class="flex items-end gap-2 h-32">
                @foreach ($daily as $day)
                    <div class="flex-1 flex flex-col items-center justify-end gap-1 group" title="{{ $day['label'] }}: {{ $day['impressions'] }} impressions, {{ $day['claims'] }} claims">
                        <div class="flex items-end gap-0.5 h-28">
                            <div class="w-2 md:w-3 rounded-t bg-champagne/70" style="height: {{ max(2, round($day['impressions'] / $maxDaily * 100)) }}%"></div>
                            <div class="w-2 md:w-3 rounded-t bg-charcoal/80" style="height: {{ max(2, round($day['claims'] / $maxDaily * 100)) }}%"></div>
                        </div>
                        <span class="text-[9px] text-muted whitespace-nowrap">{{ $day['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-serif text-lg mb-4">Top prizes</h2>
            @php $topPrizeMax = max(1, (int) ($topPrizes->max('total') ?? 0)); @endphp
            <div class="space-y-3">
                @forelse ($topPrizes as $prize)
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="truncate pr-2">{{ $prize->prize }}</span>
                            <span class="text-muted">{{ $prize->total }}</span>
                        </div>
                        <div class="mt-1 h-1.5 rounded-full bg-charcoal/10">
                            <div class="h-1.5 rounded-full bg-champagne" style="width: {{ round($prize->total / $topPrizeMax * 100) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted">No claims yet.</p>
                @endforelse
            </div>

            <h2 class="font-serif text-lg mt-6 mb-2">By mode</h2>
            <div class="space-y-1 text-sm">
                @forelse ($modes as $mode => $total)
                    <div class="flex items-center justify-between">
                        <span class="capitalize">{{ $mode }}</span>
                        <span class="text-muted">{{ $total }}</span>
                    </div>
                @empty
                    <p class="text-sm text-muted">—</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="flex items-center justify-between p-4 border-b border-charcoal/5">
            <h2 class="font-serif text-lg">Recent claims</h2>
            <a href="{{ route('admin.giveaways.index') }}" class="text-xs text-champagne-dark hover:underline">Manage giveaways →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Date</th>
                        <th class="text-left p-3">Customer</th>
                        <th class="text-left p-3">Prize</th>
                        <th class="text-left p-3">Coupon</th>
                        <th class="text-left p-3">Mode</th>
                        <th class="text-left p-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($recent as $claim)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $claim->claimed_at?->format('M j, Y g:i A') }}</td>
                            <td class="p-3">
                                <div class="font-medium">{{ $claim->customer_name ?: $claim->user?->name ?: 'Guest' }}</div>
                                <div class="text-xs text-muted">{{ $claim->email ?: $claim->user?->email }}</div>
                            </td>
                            <td class="p-3">{{ $claim->prize }}</td>
                            <td class="p-3">
                                @if ($claim->coupon)
                                    <span class="font-mono text-xs">{{ $claim->coupon->code }}</span>
                                    <div class="text-[11px] text-muted">{{ $claim->discountLabel() }}</div>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-3"><span class="badge badge-sm bg-charcoal/5 text-charcoal-soft capitalize">{{ $claim->mode }}</span></td>
                            <td class="p-3">
                                <span class="badge badge-sm {{ $claim->status === 'redeemed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                    {{ ucfirst($claim->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-sm text-muted">No claims yet. Enable the popup in Store Settings → Marketing.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($recent->hasPages())
            <div class="p-3 border-t border-charcoal/5">{{ $recent->links() }}</div>
        @endif
    </div>
@endsection
