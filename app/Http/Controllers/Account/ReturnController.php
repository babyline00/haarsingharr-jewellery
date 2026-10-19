<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRequest;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ReturnController extends Controller
{
    public function index(Request $request): View
    {
        $returns = ExchangeRequest::whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()->paginate(10);
        return view('account.returns.index', compact('returns'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_item_id' => 'nullable|exists:order_items,id',
            'type' => 'required|in:exchange,return,refund',
            'reason' => 'required|string|max:2000',
            'quantity' => 'sometimes|integer|min:1',
        ]);
        $order = Order::findOrFail($validated['order_id']);
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }

        ExchangeRequest::create(array_merge($validated, [
            'number' => ExchangeRequest::nextNumber(),
            'status' => 'pending',
        ]));
        return back()->with('success', 'Return / exchange request submitted. Our team will contact you within 24 hours.');
    }
}
