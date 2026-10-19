<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $payments = Payment::with('order')
            ->when($request->input('q'), function ($q, $term) {
                $q->where('provider_transaction_ref', 'like', "%{$term}%")
                  ->orWhere('provider_payment_id', 'like', "%{$term}%");
            })
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);

        return view('admin.payments.index', compact('payments'));
    }

    public function show(Payment $payment): View
    {
        $payment->load(['order.items', 'events']);
        return view('admin.payments.show', compact('payment'));
    }
}
