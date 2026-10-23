@extends('admin.layout')

@section('title', $giveaway->name.' — Giveaway — Haarsingharr Admin')

@section('content')
    @php
        $statusClasses = [
            'draft'     => 'bg-charcoal/10 text-charcoal-soft',
            'open'      => 'bg-emerald-100 text-emerald-800',
            'drawn'     => 'bg-champagne/30 text-charcoal',
            'cancelled' => 'bg-rose/20 text-rose',
        ];
        $entryCount = $giveaway->entries()->count();
        $winnerCount = $giveaway->winners()->count();
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <a href="{{ route('admin.giveaways.index') }}" class="text-xs text-muted hover:text-charcoal">← All giveaways</a>
            <h1 class="font-serif text-2xl md:text-3xl mt-1">{{ $giveaway->name }}</h1>
            <p class="text-sm text-muted mt-1">{{ $giveaway->prize }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $statusClasses[$giveaway->status] ?? '' }}">{{ ucfirst($giveaway->status) }}</span>
            @if (! $giveaway->isDrawn())
                @can('coupons.update')
                    <a href="{{ route('admin.giveaways.edit', $giveaway) }}" class="btn-outline text-sm">Edit</a>
                @endcan
            @endif
            @if ($giveaway->canDraw() && auth()->user()->can('coupons.update'))
                <form action="{{ route('admin.giveaways.draw', $giveaway) }}" method="post" onsubmit="return confirm('Draw {{ $giveaway->winners_count }} winner(s)? This will issue unique gift coupons and cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn-gold text-sm">Draw winners</button>
                </form>
            @endif
        </div>
    </div>

    @if ($giveaway->isDrawn())
        <div class="alert alert-success mb-6">
            Winners were drawn {{ $giveaway->drawn_at?->format('M j, Y g:i A') }}. Each winner received a unique coupon and a notification.
        </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="text-xs uppercase tracking-widest text-muted">Entries</div>
            <div class="font-serif text-2xl mt-1">{{ $entryCount }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs uppercase tracking-widest text-muted">Winners</div>
            <div class="font-serif text-2xl mt-1">{{ $winnerCount }} / {{ $giveaway->winners_count }}</div>
        </div>
        <div class="card p-4">
            <div class="text-xs uppercase tracking-widest text-muted">Coupon</div>
            <div class="text-sm mt-1">
                @if ($giveaway->coupon_type === 'percentage')
                    {{ rtrim(rtrim(number_format((float) $giveaway->coupon_value, 2, '.', ''), '0'), '.') }}% off
                @elseif ($giveaway->coupon_type === 'fixed')
                    Rs. {{ number_format((float) $giveaway->coupon_value) }} off
                @else
                    Free shipping
                @endif
            </div>
            <div class="text-[11px] text-muted mt-0.5">Expires {{ $giveaway->coupon_expiry_days }} days after issue</div>
        </div>
        <div class="card p-4">
            <div class="text-xs uppercase tracking-widest text-muted">Scheduled draw</div>
            <div class="text-sm mt-1">{{ $giveaway->draw_at?->format('M j, Y g:i A') ?? 'When manually drawn' }}</div>
        </div>
    </div>

    @if ($giveaway->description)
        <div class="card p-4 mb-6 text-sm text-charcoal-soft">{{ $giveaway->description }}</div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card overflow-hidden">
            <div class="flex items-center justify-between p-4 border-b border-charcoal/5">
                <h2 class="font-serif text-lg">Entries & winners</h2>
                <span class="text-xs text-muted">{{ $entryCount }} customer(s)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                        <tr>
                            <th class="text-left p-3">Customer</th>
                            <th class="text-left p-3">Status</th>
                            <th class="text-left p-3">Coupon</th>
                            @if (! $giveaway->isDrawn())
                                <th class="text-right p-3"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-charcoal/5">
                        @forelse ($entries as $entry)
                            <tr class="{{ $entry->is_winner ? 'bg-champagne/10' : '' }}">
                                <td class="p-3">
                                    <div class="font-medium">{{ $entry->user?->name ?? 'Removed customer' }}</div>
                                    <div class="text-xs text-muted">{{ $entry->user?->email }}</div>
                                </td>
                                <td class="p-3">
                                    @if ($entry->is_winner)
                                        <span class="badge badge-sm bg-champagne text-charcoal">Winner</span>
                                    @else
                                        <span class="badge badge-sm bg-charcoal/5 text-charcoal-soft">Entered</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if ($entry->claim?->coupon)
                                        <span class="font-mono text-xs">{{ $entry->claim->coupon->code }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                @if (! $giveaway->isDrawn())
                                    <td class="p-3 text-right">
                                        <form action="{{ route('admin.giveaways.entries.destroy', [$giveaway, $entry]) }}" method="post" onsubmit="return confirm('Remove this customer from the draw?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-danger hover:underline">Remove</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="4" class="p-8 text-center text-sm text-muted">No entries yet. Add customers on the right.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($entries->hasPages())
                <div class="p-3 border-t border-charcoal/5">{{ $entries->links() }}</div>
            @endif
        </div>

        <div class="space-y-4">
            @if (! $giveaway->isDrawn() && auth()->user()->can('coupons.update'))
                <div class="card p-4">
                    <h2 class="font-serif text-lg mb-3">Add a customer</h2>

                    <form method="get" action="{{ route('admin.giveaways.show', $giveaway) }}" class="flex gap-2 mb-3">
                        <input type="search" name="q" value="{{ $search }}" placeholder="Name, email or phone" class="flex-1 min-w-0">
                        <button type="submit" class="btn-outline text-xs">Search</button>
                    </form>

                    <div class="divide-y divide-charcoal/5 max-h-72 overflow-y-auto">
                        @forelse ($customers as $customer)
                            <div class="flex items-center justify-between gap-2 py-2">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium truncate">{{ $customer->name }}</div>
                                    <div class="text-xs text-muted truncate">{{ $customer->email }}</div>
                                </div>
                                <form action="{{ route('admin.giveaways.entries.store', $giveaway) }}" method="post">
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $customer->id }}">
                                    <button type="submit" class="btn-outline text-xs whitespace-nowrap">Add</button>
                                </form>
                            </div>
                        @empty
                            <p class="py-4 text-sm text-muted">No matching customers.</p>
                        @endforelse
                    </div>

                    <div class="border-t border-charcoal/5 mt-4 pt-4">
                        <label for="entry-email" class="text-xs">Add by email</label>
                        <form action="{{ route('admin.giveaways.entries.store', $giveaway) }}" method="post" class="flex gap-2 mt-1">
                            @csrf
                            <input id="entry-email" type="email" name="email" placeholder="customer@example.com" class="flex-1 min-w-0" required>
                            <button type="submit" class="btn-outline text-xs">Add</button>
                        </form>
                    </div>
                </div>

                <div class="card p-4 text-xs text-muted">
                    <p class="font-medium text-charcoal mb-1">How the draw works</p>
                    <p>When you draw, winners are picked at random, each receives a unique single-use coupon, and a notification is added to their account.</p>
                </div>
            @elseif ($giveaway->isDrawn())
                <div class="card p-4">
                    <h2 class="font-serif text-lg mb-2">Winners</h2>
                    <div class="space-y-2">
                        @forelse ($giveaway->winners()->with(['user', 'claim.coupon'])->get() as $winner)
                            <div class="rounded-md border border-champagne/30 bg-champagne/5 p-2">
                                <div class="text-sm font-medium">{{ $winner->user?->name }}</div>
                                <div class="text-xs text-muted">{{ $winner->user?->email }}</div>
                                @if ($winner->claim?->coupon)
                                    <div class="font-mono text-xs mt-1">{{ $winner->claim->coupon->code }}</div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-muted">No winners recorded.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
