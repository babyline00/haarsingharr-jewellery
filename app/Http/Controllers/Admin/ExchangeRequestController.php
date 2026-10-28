<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ExchangeRequest;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ExchangeRequestController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly InventoryService $inventory,
    ) {}

    public function index(Request $request): View
    {
        $exchanges = $this->filteredQuery($request)
            ->with(['order', 'orderItem', 'requestedVariant'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.exchanges.index', [
            'exchanges' => $exchanges,
            'filters' => $this->filters($request),
            'statuses' => ['pending', 'reviewing', 'approved', 'rejected', 'completed', 'cancelled'],
            'types' => ['return', 'exchange', 'refund'],
            'stats' => [
                'pending' => ExchangeRequest::whereIn('status', ['pending', 'reviewing'])->count(),
                'approved' => ExchangeRequest::where('status', 'approved')->count(),
                'awaiting_receipt' => ExchangeRequest::where('status', 'approved')->whereNull('received_at')->count(),
                'completed' => ExchangeRequest::where('status', 'completed')->count(),
            ],
        ]);
    }

    public function show(ExchangeRequest $exchange): View
    {
        $exchange->load(['order.items', 'orderItem', 'requestedVariant.product', 'refund']);

        return view('admin.exchanges.show', [
            'exchange' => $exchange,
            'timeline' => AuditLog::forTarget(ExchangeRequest::class, $exchange->id)
                ->with('actor')
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:orders,id'],
            'order_item_id' => [
                'nullable',
                Rule::exists('order_items', 'id')->where(
                    fn ($query) => $query->where('order_id', $request->integer('order_id'))
                ),
            ],
            'type' => ['required', 'in:return,exchange,refund'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'reason' => ['required', 'string', 'max:1000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'requested_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $item = isset($validated['order_item_id'])
            ? $order->items()->whereKey($validated['order_item_id'])->first()
            : null;

        if ($item && (int) $validated['quantity'] > (int) $item->quantity) {
            return back()->with('error', 'Return quantity cannot exceed the ordered quantity.');
        }

        $exchange = ExchangeRequest::create([
            'number' => ExchangeRequest::nextNumber(),
            'order_id' => $order->id,
            'order_item_id' => $item?->id,
            'type' => $validated['type'],
            'status' => 'pending',
            'reason' => $validated['reason'],
            'customer_notes' => $validated['customer_notes'] ?? null,
            'requested_variant_id' => $validated['requested_variant_id'] ?? null,
            'quantity' => $validated['quantity'],
        ]);

        $this->audit->record('exchanges.create', $exchange);

        return back()->with('success', "Return request {$exchange->number} created.");
    }

    public function update(Request $request, ExchangeRequest $exchange): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,reviewing,approved,rejected,completed,cancelled'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'return_label_url' => ['nullable', 'url', 'max:500'],
        ]);

        if (in_array($validated['status'], ['completed', 'rejected', 'cancelled'], true)
            && $validated['status'] === 'completed'
            && ! $exchange->completed_at) {
            $validated['completed_at'] = now();
        }

        $previous = $exchange->only(['status', 'admin_notes', 'refund_amount', 'return_label_url']);
        $exchange->update($validated);
        $this->audit->record('exchanges.update', $exchange, $previous, $validated);

        return back()->with('success', 'Exchange request updated.');
    }

    public function receive(Request $request, ExchangeRequest $exchange): RedirectResponse
    {
        if (in_array($exchange->status, ['rejected', 'cancelled', 'completed'], true)) {
            return back()->with('error', 'This request cannot be received in its current state.');
        }

        $validated = $request->validate([
            'restock' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($exchange, $validated) {
                $exchange->update([
                    'received_at' => $exchange->received_at ?? now(),
                    'status' => $exchange->status === 'pending' || $exchange->status === 'reviewing'
                        ? 'approved'
                        : $exchange->status,
                    'admin_notes' => trim(($exchange->admin_notes ?? '')."\n".($validated['note'] ?? 'Item received.')) ?: null,
                ]);

                if ((bool) ($validated['restock'] ?? false) && ! $exchange->restocked_at) {
                    $this->restockReturnedItem($exchange, 'Exchange return received');
                }
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The item could not be received: '.$exception->getMessage());
        }

        $this->audit->record('exchanges.receive', $exchange);

        return back()->with('success', 'Item marked received'.(($validated['restock'] ?? false) ? ' and stock returned.' : '.'));
    }

    public function complete(ExchangeRequest $exchange): RedirectResponse
    {
        if ($exchange->status === 'completed') {
            return back()->with('error', 'This request is already completed.');
        }

        if (in_array($exchange->status, ['rejected', 'cancelled'], true)) {
            return back()->with('error', 'A rejected or cancelled request cannot be completed.');
        }

        $exchange->update([
            'status' => 'completed',
            'completed_at' => $exchange->completed_at ?? now(),
        ]);

        $this->audit->record('exchanges.complete', $exchange);

        return back()->with('success', 'Exchange request completed.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->with(['order', 'orderItem'])->orderBy('id');

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'request_number', 'order_number', 'product', 'type', 'status',
                'quantity', 'reason', 'refund_amount', 'received_at',
                'restocked_at', 'completed_at', 'created_at',
            ]);

            $query->chunk(200, function ($exchanges) use ($handle) {
                foreach ($exchanges as $exchange) {
                    fputcsv($handle, [
                        $exchange->number,
                        $exchange->order?->number,
                        $exchange->orderItem?->product_name,
                        $exchange->type,
                        $exchange->status,
                        $exchange->quantity,
                        $exchange->reason,
                        $exchange->refund_amount,
                        $exchange->received_at?->toDateTimeString(),
                        $exchange->restocked_at?->toDateTimeString(),
                        $exchange->completed_at?->toDateTimeString(),
                        $exchange->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 'exchanges-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function restockReturnedItem(ExchangeRequest $exchange, string $reason): void
    {
        $item = $exchange->orderItem;

        if (! $item?->product_variant_id) {
            $exchange->update(['restocked_at' => now()]);

            return;
        }

        $variant = ProductVariant::find($item->product_variant_id);

        if ($variant) {
            $this->inventory->restock(
                $variant,
                max(1, (int) $exchange->quantity),
                $reason,
                auth()->user(),
                $exchange,
            );
        }

        $exchange->update(['restocked_at' => now()]);
    }

    private function filters(Request $request): array
    {
        return $request->only(['q', 'status', 'type', 'date_from', 'date_to']);
    }

    private function filteredQuery(Request $request): Builder
    {
        $filters = $this->filters($request);

        return ExchangeRequest::query()
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', "%{$term}%")
                        ->orWhereHas('order', fn ($order) => $order->where('number', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
    }
}
