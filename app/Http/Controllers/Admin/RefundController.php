<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Refund;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\Payment\PaymentManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class RefundController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly PaymentManager $paymentManager,
        private readonly InventoryService $inventory,
    ) {}

    public function index(Request $request): View
    {
        $refunds = $this->filteredQuery($request)
            ->with(['payment.order', 'approver'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.refunds.index', [
            'refunds' => $refunds,
            'filters' => $this->filters($request),
            'statuses' => ['pending', 'approved', 'processing', 'completed', 'failed', 'cancelled'],
            'stats' => [
                'pending' => Refund::where('status', 'pending')->count(),
                'processing' => Refund::whereIn('status', ['approved', 'processing'])->count(),
                'completed' => (float) Refund::where('status', 'completed')->sum('amount'),
                'refunded_total' => (float) Refund::where('status', 'completed')->count(),
            ],
        ]);
    }

    public function show(Refund $refund): View
    {
        $refund->load(['payment.order', 'order', 'approver']);

        return view('admin.refunds.show', [
            'refund' => $refund,
            'timeline' => AuditLog::forTarget(Refund::class, $refund->id)
                ->with('actor')
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'exists:payments,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:full,partial'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'restock' => ['nullable', 'boolean'],
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);

        $committed = (float) $payment->refunds()
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->sum('amount');

        $refundable = (float) $payment->amount
            - (float) $payment->amount_refunded
            - $committed;

        if ($refundable <= 0.009) {
            return back()->with('error', 'This payment has no refundable balance left.');
        }

        if ((float) $validated['amount'] > $refundable + 0.009) {
            return back()->with('error', 'Refund amount exceeds the remaining refundable balance of '
                .number_format($refundable, 2).'.');
        }

        $restock = (bool) ($validated['restock'] ?? false);

        if ($restock && (float) $validated['amount'] + 0.009 < (float) $payment->amount) {
            return back()->with('error', 'Restocking stock requires a full refund for the payment.');
        }

        $refund = Refund::create([
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'status' => 'pending',
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'restock_items' => $restock && $validated['type'] === 'full',
        ]);

        $this->audit->record('refunds.create', $refund);

        return back()->with('success', 'Refund requested — pending approval.');
    }

    public function approve(Request $request, Refund $refund): RedirectResponse
    {
        if (! $refund->canBeApproved()) {
            return back()->with('error', 'Refund cannot be approved in current state.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'reference' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            DB::transaction(function () use ($refund, $validated) {
                $refund->approve(auth()->user(), $validated['note'] ?? null);

                $payment = $refund->payment()->firstOrFail();

                if ($this->paymentManager->hasGateway($payment->provider)) {
                    $this->paymentManager->gateway($payment->provider)->refund($payment, $refund);
                } else {
                    // Bank transfer / other manual providers do not have a
                    // registered gateway; record the reference and complete it.
                    $refund->markCompleted(
                        $validated['reference'] ?? 'manual_refund',
                        $validated['note'] ?? 'Processed manually.',
                    );
                }

                if ($refund->fresh()->status === 'completed') {
                    $this->restockItems($refund);
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The refund could not be processed: '.$exception->getMessage());
        }

        $this->audit->record('refunds.approve', $refund);

        return back()->with('success', $refund->fresh()->status === 'completed'
            ? 'Refund approved, completed and totals updated.'
            : 'Refund approved. It is now awaiting settlement.');
    }

    public function reject(Request $request, Refund $refund): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $refund->reject(auth()->user(), $validated['note']);
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->audit->record('refunds.reject', $refund, null, ['note' => $validated['note']]);

        return back()->with('success', 'Refund rejected. No money was returned.');
    }

    public function complete(Request $request, Refund $refund): RedirectResponse
    {
        if (! in_array($refund->status, ['approved', 'processing'], true)) {
            return back()->with('error', 'Only approved or processing refunds can be marked completed.');
        }

        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($refund, $validated) {
            $refund->markCompleted(
                $validated['reference'] ?? null,
                $validated['note'] ?? 'Settlement confirmed manually.',
            );

            $this->restockItems($refund);
        });

        $this->audit->record('refunds.complete', $refund);

        return back()->with('success', 'Refund marked completed and order totals updated.');
    }

    public function receipt(Refund $refund)
    {
        if ($refund->status !== 'completed') {
            abort(404, 'A receipt is only available for completed refunds.');
        }

        $refund->load(['payment.order', 'order', 'approver']);

        $pdf = Pdf::loadView('admin.refunds.receipt', compact('refund'));

        return $pdf->download("refund-receipt-{$refund->id}.pdf");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->with(['payment.order'])->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'refund_id', 'order_number', 'payment_id', 'provider', 'amount',
                'type', 'status', 'reason', 'provider_ref', 'restocked_at',
                'approved_at', 'processed_at', 'created_at',
            ]);

            $query->chunk(200, function ($refunds) use ($handle) {
                foreach ($refunds as $refund) {
                    fputcsv($handle, [
                        $refund->id,
                        $refund->order?->number,
                        $refund->payment_id,
                        $refund->payment?->provider,
                        $refund->amount,
                        $refund->type,
                        $refund->status,
                        $refund->reason,
                        $refund->provider_ref,
                        $refund->restocked_at?->toDateTimeString(),
                        $refund->approved_at?->toDateTimeString(),
                        $refund->processed_at?->toDateTimeString(),
                        $refund->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 'refunds-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function restockItems(Refund $refund): void
    {
        if (! $refund->restock_items || $refund->restocked_at) {
            return;
        }

        $order = $refund->order()->with('items')->first();

        if (! $order) {
            $refund->update(['restocked_at' => now()]);

            return;
        }

        foreach ($order->items as $item) {
            $remaining = max(0, (int) $item->quantity - (int) $item->refunded_quantity);

            if ($remaining === 0 || ! $item->product_variant_id) {
                continue;
            }

            $variant = ProductVariant::find($item->product_variant_id);

            if ($variant) {
                $this->inventory->restock(
                    $variant,
                    $remaining,
                    'Refund restock',
                    auth()->user(),
                    $refund,
                );
            }

            $item->increment('refunded_quantity', $remaining);
            $item->update([
                'refund_amount' => (float) $item->refund_amount + ((float) $item->unit_price * $remaining),
            ]);
        }

        $refund->update(['restocked_at' => now()]);
    }

    private function filters(Request $request): array
    {
        return $request->only(['q', 'status', 'type', 'date_from', 'date_to']);
    }

    private function filteredQuery(Request $request): Builder
    {
        $filters = $this->filters($request);

        return Refund::query()
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('provider_ref', 'like', "%{$term}%")
                        ->orWhereHas('order', fn ($order) => $order->where('number', 'like', "%{$term}%"))
                        ->orWhereHas('payment', fn ($payment) => $payment->where('provider_transaction_ref', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
    }
}
