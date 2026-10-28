@extends('admin.layout')

@section('title', 'Newsletter — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Newsletter subscribers"
                    subtitle="Sign-ups from the storefront footer and account opt-ins. Export or bulk-manage consent.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.newsletter.export', request()->query())" variant="ghost" size="sm" icon="download">Export</x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <x-admin.stat label="Subscribed" :value="number_format($stats['subscribed'])" icon="check-circle" :href="route('admin.newsletter.index', ['status' => 'subscribed'])" />
        <x-admin.stat label="Pending" :value="number_format($stats['pending'])" icon="clock" :href="route('admin.newsletter.index', ['status' => 'pending'])" />
        <x-admin.stat label="Unsubscribed" :value="number_format($stats['unsubscribed'])" icon="user-minus" :href="route('admin.newsletter.index', ['status' => 'unsubscribed'])" />
        <x-admin.stat label="Bounced" :value="number_format($stats['bounced'])" icon="alert-triangle" :href="route('admin.newsletter.index', ['status' => 'bounced'])" />
        <x-admin.stat label="All records" :value="number_format($stats['total'])" icon="list" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="newsletter-search" name="q" type="search" :value="$filters['q'] ?? ''" placeholder="Subscriber email" />
            </x-admin.field>
        </div>

        @if (($filters['status'] ?? '') !== '')
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>
        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.newsletter.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.newsletter.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>
            <select name="action" class="text-sm max-w-[180px]" aria-label="Bulk action">
                <option value="unsubscribe">Unsubscribe</option>
                <option value="subscribe">Re-subscribe</option>
                <option value="bounce">Mark bounced</option>
                <option value="delete">Delete</option>
                <option value="export">Export selected</option>
            </select>
            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Newsletter subscribers">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($subscriptions->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all subscribers on this page">
                        </th>
                        <x-admin.th label="Email" />
                        <x-admin.th label="Account" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Subscribed" />
                        <x-admin.th label="Left" />
                        <x-admin.th label="Signup IP" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($subscriptions as $subscription)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $subscription->id }}"
                                       x-model="selected" aria-label="Select subscriber {{ $subscription->email }}">
                            </td>
                            <td class="p-3 font-medium">{{ $subscription->email }}</td>
                            <td class="p-3">
                                @if ($subscription->user)
                                    <a href="{{ route('admin.customers.show', $subscription->user) }}" class="text-champagne-dark hover:underline">{{ $subscription->user->email }}</a>
                                @else <span class="text-muted">Guest</span> @endif
                            </td>
                            <td class="p-3">
                                <x-admin.status-pill
                                    :status="$subscription->status === 'subscribed' ? 'approved' : ($subscription->status === 'pending' ? 'pending' : 'rejected')"
                                    :label="ucfirst($subscription->status)" />
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $subscription->subscribed_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $subscription->unsubscribed_at?->format('M j, Y') ?? '—' }}</td>
                            <td class="p-3 text-xs font-mono text-muted">{{ $subscription->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">
                            <x-admin.empty icon="mail" title="No subscribers"
                                           description="Storefront newsletter sign-ups appear here." />
                        </td></tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($subscriptions->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$subscriptions" />
                </div>
            @endif
        </x-admin.card>
    </form>
@endsection
