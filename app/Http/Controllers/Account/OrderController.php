<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->latest()->paginate(10);
        return view('account.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);
        $order->load(['items', 'addresses', 'statusHistory', 'payments']);
        return view('account.orders.show', compact('order'));
    }

    public function cancel(Order $order, CheckoutService $checkoutService): RedirectResponse
    {
        $this->authorize('cancel', $order);
        try {
            $checkoutService->cancelOrder($order, auth()->user(), 'Cancelled by customer.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Order cancelled.');
    }
}
