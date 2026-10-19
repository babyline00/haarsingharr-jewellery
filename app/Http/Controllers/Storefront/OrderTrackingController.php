<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class OrderTrackingController extends Controller
{
    public function form(): View
    {
        return view('storefront.tracking.form');
    }

    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required|string|max:30',
            'email' => ['required', 'email', Rule::when(! auth()->check(), ['max:120'])],
        ]);

        $order = Order::where('number', $validated['order_number'])->first();

        if (! $order || ($order->user_id !== auth()->id() && $order->guest_email !== $validated['email'])) {
            return back()->with('error', 'Order not found for the provided details.')->withInput();
        }

        return view('storefront.tracking.result', [
            'order' => $order->load(['items', 'statusHistory', 'shipments']),
        ]);
    }
}
