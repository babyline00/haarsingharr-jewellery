<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use App\Mail\ReviewReplyMail;
use Throwable;

class ReviewController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'q', 'rating', 'reported']);

        $counts = [
            'all'       => Review::count(),
            'pending'   => Review::where('is_approved', false)->count(),
            'approved'  => Review::where('is_approved', true)->count(),
            'featured'  => Review::where('is_featured', true)->count(),
            'reported'  => Review::where('report_count', '>', 0)->count(),
        ];

        $reviews = Review::with(['product', 'user', 'order'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', "%{$term}%")
                        ->orWhere('body', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%")
                        ->orWhereHas('user', fn ($query) => $query->where('email', 'like', "%{$term}%"))
                        ->orWhereHas('product', fn ($query) => $query->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->when($status === 'pending', fn ($q) => $q->where('is_approved', false))
                      ->when($status === 'approved', fn ($q) => $q->where('is_approved', true))
                      ->when($status === 'featured', fn ($q) => $q->where('is_featured', true))
                      ->when($status === 'reported', fn ($q) => $q->where('report_count', '>', 0));
            })
            ->when($filters['rating'] ?? null, fn ($query, $rating) => $query->where('rating', (int) $rating))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'filters' => $filters,
            'counts'  => $counts,
        ]);
    }

    public function show(Review $review): View
    {
        $review->load(['product', 'user', 'order', 'images']);

        return view('admin.reviews.show', compact('review'));
    }

    public function update(Request $request, Review $review): RedirectResponse
    {
        $validated = $request->validate([
            'admin_response' => 'nullable|string|max:1000',
            'is_featured'    => 'sometimes|boolean',
        ]);

        $previous = $review->only(['admin_response', 'is_featured']);
        $review->update($validated);

        $this->audit->record('reviews.update', $review, $previous, $validated);

        if ($review->wasChanged('admin_response') && $validated['admin_response'] && $review->user) {
            $this->notifyReply($review);
        }

        return back()->with('success', 'Review updated.');
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->approve(auth()->user());
        $this->audit->record('reviews.approve', $review);
        return back()->with('success', 'Review approved & published.');
    }

    public function reject(Review $review): RedirectResponse
    {
        $review->reject();
        $this->audit->record('reviews.reject', $review);
        return back()->with('success', 'Review rejected.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:100'],
            'ids.*'  => ['integer', 'exists:reviews,id'],
            'action' => ['required', 'in:approve,reject,feature,unfeature,delete'],
        ]);

        $reviews = Review::with('product')->whereIn('id', $validated['ids'])->get();

        foreach ($reviews as $review) {
            match ($validated['action']) {
                'approve'   => $review->approve(auth()->user()),
                'reject'    => $review->reject(),
                'feature'   => $review->update(['is_featured' => true]),
                'unfeature' => $review->update(['is_featured' => false]),
                'delete'    => (function () use ($review) {
                    $product = $review->product;
                    $review->delete();
                    $product?->updateRating();
                })(),
            };
        }

        $this->audit->record('reviews.bulk.update', null, [], [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', count($validated['ids']).' review(s) updated.');
    }

    private function notifyReply(Review $review): void
    {
        $email = $review->user?->email;

        if (! $email) {
            return;
        }

        try {
            Mail::to($email)->send(new ReviewReplyMail($review->loadMissing('product')));
            $this->audit->record('reviews.reply.sent', $review, [], ['email' => $email]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
