@extends('admin.layout')

@section('title', 'Reviews — Haarsingharr Admin')

@section('content')
    <x-admin.header title="Reviews"
                    subtitle="Moderate customer reviews before they appear on the storefront. Replies are emailed to the reviewer.">
        <x-slot:actions>
            <x-admin.button :href="route('admin.questions.index')" variant="outline" size="sm" icon="inbox">
                Product questions
            </x-admin.button>
        </x-slot:actions>
    </x-admin.header>

    <x-admin.tabs :items="collect([
        ['label' => 'All ('.$counts['all'].')', 'url' => route('admin.reviews.index'), 'active' => ($filters['status'] ?? '') === ''],
        ['label' => 'Pending ('.$counts['pending'].')', 'url' => route('admin.reviews.index', ['status' => 'pending']), 'active' => ($filters['status'] ?? '') === 'pending'],
        ['label' => 'Published ('.$counts['approved'].')', 'url' => route('admin.reviews.index', ['status' => 'approved']), 'active' => ($filters['status'] ?? '') === 'approved'],
        ['label' => 'Featured ('.$counts['featured'].')', 'url' => route('admin.reviews.index', ['status' => 'featured']), 'active' => ($filters['status'] ?? '') === 'featured'],
        ['label' => 'Reported ('.$counts['reported'].')', 'url' => route('admin.reviews.index', ['status' => 'reported']), 'active' => ($filters['status'] ?? '') === 'reported'],
    ])->values()->all()" />

    <x-admin.filter-bar>
        <div class="flex-1 min-w-[220px]">
            <x-admin.field name="q" label="Search">
                <x-admin.input id="review-search" name="q" type="search" :value="$filters['q'] ?? ''"
                               placeholder="Review text, reviewer, product" />
            </x-admin.field>
        </div>

        <div class="w-full sm:w-32">
            <x-admin.field name="rating" label="Rating">
                <x-admin.select id="review-rating" name="rating" :selected="$filters['rating'] ?? ''"
                                placeholder="Any"
                                :options="collect(range(5, 1))->mapWithKeys(fn ($r) => [$r => str_repeat('★', $r).' & up'])->all()" />
            </x-admin.field>
        </div>

        @if (($filters['status'] ?? '') !== '')
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif

        <x-admin.button type="submit" variant="outline" size="sm" icon="filter">Apply</x-admin.button>

        @if (array_filter($filters ?? []))
            <x-admin.button :href="route('admin.reviews.index')" variant="ghost" size="sm">Clear</x-admin.button>
        @endif
    </x-admin.filter-bar>

    <form method="POST" action="{{ route('admin.reviews.bulk') }}" x-data="{ selected: [] }">
        @csrf

        <div x-show="selected.length" x-cloak
             class="card p-3 mb-3 flex flex-wrap items-center gap-3 border-champagne/40 bg-ivory-alt/40">
            <span class="text-sm font-medium" x-text="`${selected.length} selected`"></span>

            <select name="action" class="text-sm max-w-[180px]" aria-label="Bulk action">
                <option value="approve">Approve &amp; publish</option>
                <option value="reject">Unpublish</option>
                <option value="feature">Feature</option>
                <option value="unfeature">Unfeature</option>
                <option value="delete">Delete</option>
            </select>

            <x-admin.button type="submit" size="sm" variant="outline">Apply to selected</x-admin.button>
        </div>

        <x-admin.card :padding="false">
            <x-admin.table caption="Reviews">
                <thead>
                    <tr>
                        <th scope="col" class="p-3 w-10">
                            <span class="sr-only">Select all</span>
                            <input type="checkbox"
                                   x-on:change="selected = $event.target.checked ? @js($reviews->pluck('id')->map(fn ($id) => (string) $id)->all()) : []"
                                   aria-label="Select all reviews on this page">
                        </th>
                        <x-admin.th label="Product" />
                        <x-admin.th label="Reviewer" />
                        <x-admin.th label="Rating" />
                        <x-admin.th label="Review" />
                        <x-admin.th label="Reply" />
                        <x-admin.th label="Status" />
                        <x-admin.th label="Date" />
                        <x-admin.th label="Actions" align="right" />
                    </tr>
                </thead>

                <tbody class="divide-y divide-charcoal/5">
                    @forelse ($reviews as $review)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                <input type="checkbox" name="ids[]" value="{{ $review->id }}"
                                       x-model="selected" aria-label="Select review {{ $review->id }}">
                            </td>
                            <td class="p-3">
                                @if ($review->product)
                                    <a href="{{ route('admin.products.edit', $review->product) }}"
                                       class="font-medium text-champagne-dark hover:underline">{{ $review->product->name }}</a>
                                @else
                                    <span class="text-muted">Product removed</span>
                                @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">
                                {{ $review->user?->email ?? $review->customer_name ?? 'Guest' }}
                                @if ($review->is_verified_purchase) <span class="block text-xs text-emerald-700">Verified purchase</span> @endif
                            </td>
                            <td class="p-3 text-amber-500 whitespace-nowrap">{{ str_repeat('★', $review->rating) }}<span class="text-charcoal/20">{{ str_repeat('★', 5 - $review->rating) }}</span></td>
                            <td class="p-3 max-w-[240px]">{{ \Illuminate\Support\Str::limit($review->title ?: $review->body, 50) }}</td>
                            <td class="p-3 text-xs">
                                @if ($review->admin_response)
                                    <span class="text-emerald-700">Replied</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="p-3">
                                <x-admin.status-pill :status="$review->is_approved ? 'approved' : 'pending'" :label="$review->is_approved ? 'Published' : 'Pending'" />
                                @if ($review->is_featured) <span class="badge bg-champagne/20 text-champagne-dark">Featured</span> @endif
                                @if ($review->report_count > 0) <span class="badge bg-rose-100 text-rose-800">{{ $review->report_count }} reports</span> @endif
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $review->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.reviews.show', $review) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <x-admin.empty icon="star" title="No reviews found"
                                               description="Reviews appear here once customers submit them on the storefront." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-admin.table>

            @if ($reviews->total() > 0)
                <div class="px-4 border-t border-charcoal/5">
                    <x-admin.pagination :paginator="$reviews" />
                </div>
            @endif
        </x-admin.card>
    </form>
@endsection
