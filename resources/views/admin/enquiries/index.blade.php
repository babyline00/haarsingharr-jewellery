@extends('admin.layout')

@section('title', 'Enquiries — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Contact enquiries"
                    subtitle="Inbox from the storefront contact form — assign, reply by email and resolve.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.enquiries.export', request()->query())" variant="ghost" size="sm" icon="download">Export</x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
        <x-admin.stat label="New" :value="number_format($counts['new'])" icon="inbox" :href="route('admin.enquiries.index', ['status' => 'new'])" />
        <x-admin.stat label="Open" :value="number_format($counts['open'])" icon="loader" :href="route('admin.enquiries.index', ['status' => 'open'])" />
        <x-admin.stat label="Resolved" :value="number_format($counts['resolved'])" icon="check-circle" :href="route('admin.enquiries.index', ['status' => 'resolved'])" />
        <x-admin.stat label="Closed" :value="number_format($counts['closed'])" icon="archive" :href="route('admin.enquiries.index', ['status' => 'closed'])" />
        <x-admin.stat label="Mine (unresolved)" :value="number_format($counts['mine'])" icon="user" :href="route('admin.enquiries.index', ['assigned' => 'me'])" />
    </div>

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[200px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="enquiry-search" name="q" type="search" :value="$filters['q'] ?? ''" placeholder="Name, email, subject or message" />
            </x-admin.field>
        </div>
        <div class="w-full sm:w-40">
            <x-admin.field name="type" label="Type">
                <x-admin.select id="enquiry-type" name="type" :selected="$filters['type'] ?? ''" placeholder="All types"
                                :options="collect($types)->mapWithKeys(fn ($t) => [$t => ucfirst($t)])->all()" />
            </x-admin.field>
        </div>
        <div class="w-full sm:w-40">
            <x-admin.field name="assigned" label="Assigned">
                <x-admin.select id="enquiry-assigned" name="assigned" :selected="$filters['assigned'] ?? ''" placeholder="Anyone"
                                :options="['me' => 'Assigned to me'] + $staff->mapWithKeys(fn ($s) => [$s->id => $s->name])->all()" />
            </x-admin.field>
        </div>

        @if (($filters['status'] ?? '') !== '')
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>
        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.enquiries.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.enquiries.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>
            <select name="action" class="text-sm max-w-[160px]" aria-label="Bulk action">
                <option value="open">Mark open</option>
                <option value="resolve">Mark resolved</option>
                <option value="close">Mark closed</option>
                <option value="export">Export selected</option>
            </select>
            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Enquiries">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($enquiries->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all enquiries on this page">
                        </th>
                        <x-admin.th label="From" />
                        <x-admin.th label="Subject / message" />
                        <x-admin.th label="Type" />
                        <x-admin.th label="Assigned" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Received" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($enquiries as $enquiry)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $enquiry->id }}"
                                       x-model="selected" aria-label="Select enquiry from {{ $enquiry->name }}">
                            </td>
                            <td class="p-3">
                                <span class="font-medium">{{ $enquiry->name }}</span>
                                <span class="block text-xs text-muted">{{ $enquiry->email }}</span>
                                @if ($enquiry->phone) <span class="block text-xs text-muted">{{ $enquiry->phone }}</span> @endif
                            </td>
                            <td class="p-3 max-w-[280px]">
                                @if ($enquiry->subject) <div class="font-medium">{{ $enquiry->subject }}</div> @endif
                                <div class="text-xs text-muted">{{ \Illuminate\Support\Str::limit($enquiry->message, 80) }}</div>
                            </td>
                            <td class="p-3 text-xs uppercase tracking-widest text-muted">{{ ucfirst($enquiry->type) }}</td>
                            <td class="p-3 text-sm whitespace-nowrap">
                                @if ($enquiry->assigned_to === auth()->id()) <span class="badge bg-champagne/20 text-champagne-dark">Me</span>
                                @elseif ($enquiry->assignee) {{ $enquiry->assignee->name }}
                                @else <span class="text-muted">—</span> @endif
                            </td>
                            <td class="p-3"><x-admin.status-pill :status="$enquiry->status === 'new' ? 'pending' : $enquiry->status" :label="ucfirst($enquiry->status)" /></td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $enquiry->created_at?->format('M j, H:i') }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">
                            <x-admin.empty icon="inbox" title="Inbox zero"
                                           description="New contact-form enquiries appear here." />
                        </td></tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($enquiries->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$enquiries" />
                </div>
            @endif
        </x-admin.card>
    </form>
@endsection
