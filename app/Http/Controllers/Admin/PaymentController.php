<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $payments = $this->filteredQuery($request)
            ->with('order')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'filters' => $this->filters($request),
            'providers' => Payment::query()->distinct()->orderBy('provider')->pluck('provider'),
            'statuses' => ['pending', 'initiated', 'authorized', 'captured', 'failed', 'cancelled', 'refunded', 'partially_refunded'],
            'stats' => [
                'captured' => (float) Payment::whereIn('status', ['captured', 'partially_refunded', 'refunded'])->sum('amount_captured'),
                'refunded' => (float) Payment::sum('amount_refunded'),
                'pending' => Payment::whereIn('status', ['pending', 'initiated', 'authorized'])->count(),
                'failed' => Payment::where('status', 'failed')->count(),
            ],
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['order.items', 'order.addresses', 'events', 'refunds']);

        $committed = (float) $payment->refunds
            ->whereIn('status', ['pending', 'approved', 'processing'])
            ->sum('amount');

        return view('admin.payments.show', [
            'payment' => $payment,
            'refundable' => max(0, (float) $payment->amount - (float) $payment->amount_refunded - $committed),
        ]);
    }

    public function capture(Request $request, Payment $payment): RedirectResponse
    {
        if (in_array($payment->status, ['captured', 'refunded', 'partially_refunded'], true)) {
            return back()->with('error', 'This payment is already captured or refunded.');
        }

        $validated = $request->validate([
            'reference' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($payment, $validated) {
            if (! empty($validated['reference'])) {
                $payment->update([
                    'provider_transaction_ref' => $validated['reference'],
                    'provider_status' => 'manually_captured',
                ]);
            }

            $payment->markCaptured();

            $payment->events()->create([
                'type' => 'captured',
                'status' => 'captured',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'note' => $validated['note'] ?: 'Payment captured manually by staff.',
                'is_verified' => true,
            ]);

            $order = $payment->order;

            if ($order && $order->status === OrderStatus::Pending) {
                $order->transitionTo(OrderStatus::Confirmed, auth()->user(), 'Payment captured manually.');
            }
        });

        $this->audit->record('payments.capture', $payment);

        return back()->with('success', 'Payment marked captured and order totals updated.');
    }

    public function fail(Request $request, Payment $payment): RedirectResponse
    {
        if (in_array($payment->status, ['captured', 'refunded', 'partially_refunded'], true)) {
            return back()->with('error', 'A captured payment cannot be marked failed.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $payment->markFailed($validated['reason']);

        $order = $payment->order;

        if ($order && (float) $order->paid_amount <= 0) {
            $order->update(['payment_status' => 'failed']);
        }

        $this->audit->record('payments.fail', $payment, null, ['reason' => $validated['reason']]);

        return back()->with('success', 'Payment marked failed.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->with('order')->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'payment_id', 'order_number', 'provider', 'method', 'status',
                'amount', 'captured', 'refunded', 'currency', 'provider_ref',
                'provider_payment_id', 'provider_status', 'initiated_at',
                'captured_at', 'failed_at', 'created_at',
            ]);

            $query->chunk(200, function ($payments) use ($handle) {
                foreach ($payments as $payment) {
                    fputcsv($handle, [
                        $payment->id,
                        $payment->order?->number,
                        $payment->provider,
                        $payment->method,
                        $payment->status,
                        $payment->amount,
                        $payment->amount_captured,
                        $payment->amount_refunded,
                        $payment->currency,
                        $payment->provider_transaction_ref,
                        $payment->provider_payment_id,
                        $payment->provider_status,
                        $payment->initiated_at?->toDateTimeString(),
                        $payment->captured_at?->toDateTimeString(),
                        $payment->failed_at?->toDateTimeString(),
                        $payment->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 'payments-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filters(Request $request): array
    {
        return $request->only(['q', 'status', 'provider', 'date_from', 'date_to']);
    }

    private function filteredQuery(Request $request): Builder
    {
        $filters = $this->filters($request);

        return Payment::query()
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('provider_transaction_ref', 'like', "%{$term}%")
                        ->orWhere('provider_payment_id', 'like', "%{$term}%")
                        ->orWhereHas('order', fn ($order) => $order->where('number', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['provider'] ?? null, fn ($q, $provider) => $q->where('provider', $provider))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
    }
}
