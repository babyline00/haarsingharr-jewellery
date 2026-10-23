<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExchangeRequest;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExchangeRequestController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $exchanges = ExchangeRequest::with(['order', 'orderItem', 'requestedVariant'])
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->when($request->input('q'), function ($q, $term) {
                $q->where('number', 'like', "%{$term}%")
                  ->orWhereHas('order', fn ($q) => $q->where('number', 'like', "%{$term}%"));
            })
            ->latest()
            ->paginate(20);

        return view('admin.exchanges.index', [
            'exchanges' => $exchanges,
            'filters'   => $request->only(['status', 'type', 'q']),
        ]);
    }

    public function show(ExchangeRequest $exchange): View
    {
        $exchange->load(['order.items', 'orderItem', 'requestedVariant.product', 'refund']);
        return view('admin.exchanges.show', compact('exchange'));
    }

    public function update(Request $request, ExchangeRequest $exchange)
    {
        $validated = $request->validate([
            'status'         => 'required|in:pending,reviewing,approved,rejected,completed,cancelled',
            'admin_notes'    => 'nullable|string|max:2000',
            'refund_amount'  => 'nullable|numeric|min:0',
        ]);

        $previous = $exchange->only(['status', 'admin_notes', 'refund_amount']);
        $exchange->update($validated);
        $this->audit->record('exchanges.update', $exchange, $previous, $validated);

        return back()->with('success', 'Exchange request updated.');
    }
}
