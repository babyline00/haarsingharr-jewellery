<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\AuditService;
use App\Services\Payment\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly PaymentManager $paymentManager,
    ) {}

    public function index(Request $request): View
    {
        $refunds = Refund::with(['payment.order', 'approver'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20);
        return view('admin.refunds.index', compact('refunds'));
    }

    public function show(Refund $refund): View
    {
        $refund->load(['payment.order', 'approver']);
        return view('admin.refunds.show', compact('refund'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:full,partial',
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);
        if ((float) $validated['amount'] > $payment->amount) {
            return back()->with('error', 'Refund amount exceeds payment amount.');
        }

        $refund = Refund::create(array_merge($validated, [
            'order_id' => $payment->order_id,
            'status' => 'pending',
        ]));
        $this->audit->record('refunds.create', $refund);
        return back()->with('success', 'Refund requested — pending approval.');
    }

    public function approve(Refund $refund)
    {
        if (! $refund->canBeApproved()) {
            return back()->with('error', 'Refund cannot be approved in current state.');
        }
        $refund->approve(auth()->user());
        $payment = $refund->payment;

        // Use the gateway to process the actual refund (for online gateways)
        $gateway = $this->paymentManager->gateway($payment->provider);
        $gateway->refund($payment, $refund);

        $this->audit->record('refunds.approve', $refund);
        return back()->with('success', 'Refund approved & processed.');
    }
}
