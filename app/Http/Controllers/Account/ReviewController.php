<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id' => 'nullable|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:120',
            'body' => 'required|string|min:10|max:5000',
        ]);

        // Verify purchase if require_purchase is enabled
        $verified = false;
        if (! empty($validated['order_id'])) {
            $order = Order::find($validated['order_id']);
            if ($order && $order->user_id === $request->user()->id && $order->items()->where('product_id', $validated['product_id'])->exists()) {
                $verified = true;
            }
        }
        if (config('storefront.reviews.require_purchase') && ! $verified) {
            return back()->with('error', 'You can only review products you have purchased.');
        }

        $review = Review::create(array_merge($validated, [
            'user_id' => $request->user()->id,
            'customer_name' => $request->user()->name,
            'is_verified_purchase' => $verified,
            'is_approved' => config('storefront.reviews.auto_approve', false),
        ]));

        return back()->with('success', config('storefront.reviews.auto_approve', false)
            ? 'Review published.'
            : 'Review submitted — pending approval.');
    }
}
