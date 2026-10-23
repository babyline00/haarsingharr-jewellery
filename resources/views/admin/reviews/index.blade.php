@extends('admin.layout')

@section('title', 'Reviews — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Reviews</h1>
            <p class="text-sm text-muted mt-1">Moderate customer reviews before they appear on the storefront.</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        <a href="{{ route('admin.reviews.index') }}" class="btn-outline text-sm {{ request('status') ? '' : 'bg-champagne/15' }}">All</a>
        <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="btn-outline text-sm {{ request('status') === 'pending' ? 'bg-champagne/15' : '' }}">Pending</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-ivory-alt/30 text-xs uppercase tracking-widest text-muted">
                    <tr>
                        <th class="text-left p-3">Product</th>
                        <th class="text-left p-3">Reviewer</th>
                        <th class="text-left p-3">Rating</th>
                        <th class="text-left p-3">Title</th>
                        <th class="text-left p-3">Status</th>
                        <th class="text-left p-3">Date</th>
                        <th class="text-right p-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-charcoal/5">
                    @foreach ($reviews as $review)
                        <tr class="hover:bg-ivory-alt/30 transition-colors">
                            <td class="p-3">
                                @if ($review->product)
                                    <a href="{{ route('admin.products.edit', $review->product) }}" class="font-medium hover:text-champagne-dark transition-colors">{{ $review->product->name }}</a>
                                @else — @endif
                            </td>
                            <td class="p-3 text-charcoal-soft">
                                {{ $review->user?->email ?? $review->customer_name ?? 'Guest' }}
                                @if ($review->is_verified_purchase) <div class="text-xs text-emerald-700">Verified purchase</div> @endif
                            </td>
                            <td class="p-3 text-amber-500 whitespace-nowrap">{{ str_repeat('★', $review->rating) }}<span class="text-charcoal/20">{{ str_repeat('★', 5 - $review->rating) }}</span></td>
                            <td class="p-3">{{ Str::limit($review->title ?: $review->body, 50) }}</td>
                            <td class="p-3">
                                <span class="badge {{ $review->is_approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $review->is_approved ? 'Approved' : 'Pending' }}</span>
                                @if ($review->is_featured) <span class="badge bg-champagne/20 text-champagne-dark">Featured</span> @endif
                            </td>
                            <td class="p-3 text-xs text-muted whitespace-nowrap">{{ $review->created_at?->format('M j, Y') }}</td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.reviews.show', $review) }}" class="text-xs text-champagne-dark hover:underline">View</a>
                                @if (! $review->is_approved)
                                    @can('reviews.moderate')
                                    <form action="{{ route('admin.reviews.approve', $review) }}" method="post" class="inline">
                                        @csrf
                                        <button class="text-xs text-emerald-700 hover:underline ml-2">Approve</button>
                                    </form>
                                    @endcan
                                @else
                                    @can('reviews.moderate')
                                    <form action="{{ route('admin.reviews.reject', $review) }}" method="post" class="inline">
                                        @csrf
                                        <button class="text-xs text-danger hover:underline ml-2">Unpublish</button>
                                    </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    @if ($reviews->isEmpty())
                        <tr><td colspan="7" class="p-8 text-center text-muted text-sm">No reviews found.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-charcoal/5">{{ $reviews->links() }}</div>
    </div>
@endsection
