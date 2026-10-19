<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $reviews = Review::with(['product', 'user', 'order'])
            ->when($request->input('status') === 'pending', fn ($q) => $q->where('is_approved', false))
            ->latest()
            ->paginate(20);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function show(Review $review): View
    {
        return view('admin.reviews.show', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $validated = $request->validate([
            'admin_response' => 'nullable|string|max:1000',
            'is_featured' => 'sometimes|boolean',
        ]);
        $review->update($validated);
        $this->audit->record('reviews.update', $review);
        return back()->with('success', 'Review updated.');
    }

    public function approve(Review $review)
    {
        $review->approve(auth()->user());
        $this->audit->record('reviews.approve', $review);
        return back()->with('success', 'Review approved & published.');
    }

    public function reject(Review $review)
    {
        $review->reject();
        $this->audit->record('reviews.reject', $review);
        return back()->with('success', 'Review rejected.');
    }
}
