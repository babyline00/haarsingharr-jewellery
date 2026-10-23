@extends('admin.layout')

@section('title', 'Giveaways — Haarsingharr Admin')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Giveaways</h1>
            <p class="text-sm text-muted mt-1">Add customers, run a draw and issue a unique gift coupon to each winner.</p>
        </div>
        @can('coupons.create')
            <a href="{{ route('admin.giveaways.create') }}" class="btn-gold text-sm">New Giveaway</a>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Giveaway</th>
                        <th class="text-left p-3">Prize</th>
                        <th class="text-right p-3">Entries</th>
                        <th class="text-right p-3">Winners</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Drawn</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($giveaways as $giveaway)
                        @php
                            $statusClasses = [
                                'draft'     => 'bg-charcoal/10 text-charcoal-soft',
                                'open'      => 'bg-emerald-100 text-emerald-800',
                                'drawn'     => 'bg-champagne/30 text-charcoal',
                                'cancelled' => 'bg-rose/20 text-rose',
                            ];
                        @endphp
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <a href="{{ route('admin.giveaways.show', $giveaway) }}" class="font-medium text-champagne-dark hover:underline">{{ $giveaway->name }}</a>
                                @if ($giveaway->description)
                                    <div class="text-xs text-muted line-clamp-1">{{ $giveaway->description }}</div>
                                @endif
                            </td>
                            <td class="p-3">{{ $giveaway->prize }}</td>
                            <td class="p-3 text-right">{{ $giveaway->entries_count }}</td>
                            <td class="p-3 text-right">{{ $giveaway->winners_total }} / {{ $giveaway->winners_count }}</td>
                            <td class="p-3"><span class="badge badge-sm {{ $statusClasses[$giveaway->status] ?? '' }}">{{ ucfirst($giveaway->status) }}</span></td>
                            <td class="p-3 text-xs text-muted">{{ $giveaway->drawn_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.giveaways.show', $giveaway) }}" class="text-xs text-champagne-dark hover:underline">Open</a>
                                @if (! $giveaway->isDrawn())
                                    @can('coupons.update')
                                        <a href="{{ route('admin.giveaways.edit', $giveaway) }}" class="ml-3 text-xs text-charcoal-soft hover:underline">Edit</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-sm text-muted">No giveaways yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $giveaways->links() }}</div>
    </div>
@endsection
