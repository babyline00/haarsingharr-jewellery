@extends('admin.layout')

@section('title', 'Review #' . $review->id . ' — Haarsingharr Admin')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="font-serif text-2xl md:text-3xl">Review #{{ $review->id }}</h1>
            <p class="text-sm text-muted mt-1">
                {{ $review->product?->name ?? 'Unknown product' }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="badge {{ $review->is_approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $review->is_approved ? 'Approved' : 'Pending' }}</span>
            <a href="{{ route('admin.reviews.index') }}" class="btn-ghost text-sm">Back</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <div class="space-y-4">
            <div class="card p-5">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-charcoal/20">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                    @if ($review->is_verified_purchase) <span class="badge bg-emerald-100 text-emerald-800">Verified purchase</span> @endif
                </div>
                @if ($review->title) <h2 class="font-serif text-lg mb-2">{{ $review->title }}</h2> @endif
                <p class="text-sm whitespace-pre-line">{{ $review->body }}</p>
                @if ($review->images->isNotEmpty())
                    <div class="flex gap-2 mt-4">
                        @foreach ($review->images as $image)
                            <img src="{{ $image->url ?? $image->path }}" alt="" class="w-24 h-24 object-cover rounded bg-ivory-alt">
                        @endforeach
                    </div>
                @endif
                <div class="text-xs text-muted mt-4">
                    By {{ $review->user?->email ?? $review->customer_name ?? 'Guest' }}
                    @if ($review->order) · Order {{ $review->order->number }} @endif
                    · {{ $review->created_at?->format('M j, Y H:i') }}
                </div>
            </div>

            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Admin Response</h2>
                @can('reviews.update')
                <form action="{{ route('admin.reviews.update', $review) }}" method="post">
                    @csrf @method('PUT')
<<<<<<< HEAD
                    <textarea name="admin_response" rows="4" placeholder="Public response visible under the review">{{ old('admin_response', $review->admin_response) }}</textarea>
=======
                    <textarea name="admin_response" rows="4" class="ckeditor" placeholder="Public response visible under the review">{{ old('admin_response', $review->admin_response) }}</textarea>
>>>>>>> origin/main
                    @error('admin_response') <p class="text-xs text-danger">{{ $message }}</p> @enderror
                    <label class="flex items-center gap-2 text-sm mt-3">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $review->is_featured))>
                        Feature this review
                    </label>
                    <button type="submit" class="btn-gold mt-4">Save Response</button>
                </form>
                @else
                    <p class="text-sm text-muted">{{ $review->admin_response ?: 'No response yet.' }}</p>
                @endcan
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <h2 class="font-serif text-lg mb-3">Moderation</h2>
                <div class="space-y-2">
                    @if (! $review->is_approved)
                        @can('reviews.moderate')
                        <form action="{{ route('admin.reviews.approve', $review) }}" method="post">
                            @csrf
                            <button class="btn-gold w-full">Approve &amp; Publish</button>
                        </form>
                        @endcan
                    @else
                        @can('reviews.moderate')
                        <form action="{{ route('admin.reviews.reject', $review) }}" method="post">
                            @csrf
                            <button class="btn-danger w-full">Unpublish</button>
                        </form>
                        @endcan
                    @endif
                </div>
                <dl class="mt-4 text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-muted">Helpful votes</dt><dd>{{ $review->helpful_count }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Reports</dt><dd>{{ $review->report_count }}</dd></div>
                    <div class="flex justify-between"><dt class="text-muted">Approved</dt><dd>{{ $review->approved_at?->format('M j, Y') ?? '—' }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>
@endsection
