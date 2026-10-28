<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderNote;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\ShipmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly InventoryService $inventory,
        private readonly ShipmentService $shipments,
    ) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $orders = $this->filteredQuery($request)
            ->with(['user', 'items', 'addresses'])
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'channels' => Order::query()->whereNotNull('order_channel')->distinct()->orderBy('order_channel')->pluck('order_channel'),
            'views' => $this->savedViews($filters),
            'stats' => [
                'today' => Order::whereDate('created_at', today())->count(),
                'action' => Order::whereIn('status', ['pending', 'confirmed', 'processing', 'packed'])->count(),
                'unpaid' => Order::whereIn('payment_status', ['unpaid', 'pending', 'failed'])
                    ->whereNotIn('status', ['cancelled', 'returned'])->count(),
                'month' => (float) Order::where('payment_status', 'paid')
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('grand_total'),
            ],
        ]);
    }

    public function show(Order $order): View
    {
        $order->load([
            'items.product', 'addresses', 'statusHistory.actor',
            'payments.events', 'payments.refunds', 'refunds.payment',
            'shipments', 'notes.user', 'exchangeRequests.orderItem', 'exchangeRequests.refund',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
            'allowedTransitions' => $order->status->allowedTransitions(),
            'itemStatuses' => ['unfulfilled', 'packed', 'shipped', 'delivered', 'returned', 'cancelled'],
            'variants' => ProductVariant::with('product')
                ->where('status', 'active')
                ->orderBy('sku')
                ->limit(500)
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,packed,shipped,delivered,cancelled,returned,partially_fulfilled',
            'note' => 'nullable|string|max:500',
        ]);

        $newStatus = OrderStatus::from($validated['status']);

        if ($newStatus === OrderStatus::Cancelled) {
            return $this->cancelOrder($order, $validated['note'] ?? null);
        }

        if (! $order->status->canTransitionTo($newStatus)) {
            return back()->with('error', "Cannot transition from {$order->status->label()} to {$newStatus->label()}.");
        }

<<<<<<< HEAD
        $previous = $order->status;
=======
>>>>>>> origin/main
        $order->transitionTo($newStatus, auth()->user(), $validated['note'] ?? null);
        $this->audit->record('orders.status.update', $order,
            ['status' => $previous->value],
            ['status' => $newStatus->value],
        );

        return back()->with('success', 'Order status updated.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['integer', 'exists:orders,id'],
            'action' => ['required', 'in:status,export,print,cancel'],
            'status' => ['nullable', 'required_if:action,status', 'in:pending,confirmed,processing,packed,shipped,delivered,returned,partially_fulfilled'],
        ]);

        $ids = $validated['ids'];

        if ($validated['action'] === 'export') {
            return redirect()->route('admin.orders.export', ['ids' => implode(',', $ids)]);
        }

        if ($validated['action'] === 'print') {
            return redirect()->route('admin.orders.print', ['ids' => implode(',', $ids)]);
        }

        $orders = Order::with('items')->whereIn('id', $ids)->get();
        $changed = 0;
        $skipped = 0;

        if ($validated['action'] === 'cancel') {
            foreach ($orders as $order) {
                try {
                    $this->cancelWithRestock($order, 'Bulk cancelled by staff.');
                    $changed++;
                } catch (Throwable) {
                    $skipped++;
                }
            }
        } else {
            $newStatus = OrderStatus::from($validated['status']);

            foreach ($orders as $order) {
                if (! $order->status->canTransitionTo($newStatus)) {
                    $skipped++;
                    continue;
                }

                $order->transitionTo($newStatus, auth()->user(), 'Bulk status update.');
                $changed++;
            }
        }

        return back()->with(
            $changed > 0 ? 'success' : 'error',
            "{$changed} order(s) updated".($skipped ? ", {$skipped} skipped because their current status does not allow it." : '.')
        );
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->cancelOrder($order, $validated['reason'] ?? null);
    }

    public function storeNote(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $note = $order->notes()->create([
            'user_id' => auth()->id(),
            'body' => $validated['body'],
            'is_customer_visible' => false,
        ]);

        $this->audit->record('orders.notes.create', $note);

        return back()->with('success', 'Internal note added.');
    }

    public function destroyNote(Order $order, OrderNote $note): RedirectResponse
    {
        abort_unless($note->order_id === $order->id, 404);

        $note->delete();
        $this->audit->record('orders.notes.delete', $order, ['note' => $note->body]);

        return back()->with('success', 'Note removed.');
    }

    public function updateAddress(Request $request, Order $order, string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['billing', 'shipping'], true), 404);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone_country' => ['required', 'string', 'max:5'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'size:2'],
            'postal_code' => ['nullable', 'string', 'max:10'],
        ]);

        $previous = $order->addresses()->where('type', $type)->first()?->only(array_keys($validated));

        $order->addresses()->updateOrCreate(
            ['type' => $type],
            array_merge($validated, ['order_id' => $order->id]),
        );

        $this->audit->record("orders.addresses.{$type}.update", $order, $previous, $validated);

        return back()->with('success', ucfirst($type).' address updated.');
    }

    public function updateItemFulfilment(Request $request, Order $order, int $item): RedirectResponse
    {
        $validated = $request->validate([
            'fulfilment_status' => ['required', 'in:unfulfilled,packed,shipped,delivered,returned,cancelled'],
        ]);

        $orderItem = $order->items()->whereKey($item)->firstOrFail();
        $previous = $orderItem->fulfilment_status;
        $orderItem->update(['fulfilment_status' => $validated['fulfilment_status']]);

        $statuses = $order->items()->pluck('fulfilment_status');
        $order->update([
            'fulfilment_status' => $statuses->every(fn ($status) => $status === 'delivered')
                ? 'fulfilled'
                : ($statuses->every(fn ($status) => $status === 'unfulfilled') ? 'unfulfilled' : 'partial'),
        ]);

        $this->audit->record('orders.items.fulfilment.update', $orderItem,
            ['fulfilment_status' => $previous],
            ['fulfilment_status' => $validated['fulfilment_status']],
        );

        return back()->with('success', 'Item fulfilment updated.');
    }

    public function storePayment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:cod,bank_transfer,jazzcash,easypaisa,other'],
            'method' => ['nullable', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:80'],
            'note' => ['nullable', 'string', 'max:1000'],
            'mark_paid' => ['nullable', 'boolean'],
        ]);

        $outstanding = $order->outstandingAmount();

        if ((float) $validated['amount'] > $outstanding + 0.009) {
            return back()->with('error', 'Payment exceeds the outstanding balance of Rs. '.number_format($outstanding, 2).'.');
        }

        $markPaid = (bool) ($validated['mark_paid'] ?? false);

        $payment = DB::transaction(function () use ($order, $validated, $markPaid) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'provider' => $validated['provider'],
                'method' => ($validated['method'] ?? null) ?: 'manual',
                'status' => 'pending',
                'amount' => $validated['amount'],
                'currency' => $order->currency,
                'provider_status' => $markPaid ? 'manually_captured' : 'manual_pending',
                'provider_transaction_ref' => $validated['reference'] ?? null,
                'initiated_at' => now(),
            ]);

            $payment->events()->create([
                'type' => 'initiated',
                'status' => 'pending',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'note' => ($validated['note'] ?? null) ?: 'Manual payment recorded by staff.',
            ]);

            if ($markPaid) {
                $payment->markCaptured();
                $payment->events()->create([
                    'type' => 'captured',
                    'status' => 'captured',
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'note' => 'Marked captured manually by staff.',
                    'is_verified' => true,
                ]);
            }

            return $payment;
        });

        $this->audit->record('orders.payments.create', $payment);

        return back()->with('success', $markPaid
            ? 'Manual payment recorded and marked captured.'
            : 'Manual payment recorded as pending.');
    }

    public function resendEmail(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:confirmation,invoice'],
        ]);

        $email = $order->user?->email ?: $order->guest_email;

        if (! $email) {
            return back()->with('error', 'This order has no email address on file.');
        }

        try {
            Mail::to($email)->send(new OrderConfirmationMail(
                $order->loadMissing(['items', 'addresses', 'user']),
                $validated['type'] === 'invoice',
            ));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'The email could not be sent. Check the mail settings and try again.');
        }

        $this->audit->record("orders.email.{$validated['type']}", $order);

        return back()->with('success', $validated['type'] === 'invoice'
            ? "Invoice emailed to {$email}."
            : "Order confirmation emailed to {$email}.");
    }

    public function storeShipment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'courier' => ['required', 'string', 'max:80'],
            'method' => ['nullable', 'string', 'max:80'],
            'tracking_number' => ['required', 'string', 'max:80'],
            'tracking_url' => ['nullable', 'url', 'max:300'],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'weight_grams' => ['nullable', 'numeric', 'min:0'],
            'estimated_delivery_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $shipment = $this->shipments->create($order, $validated, auth()->user());
        $this->audit->record('orders.shipments.create', $shipment);

        return back()->with('success', 'Shipment recorded.');
    }

    public function updateShipment(Request $request, Order $order, Shipment $shipment): RedirectResponse
    {
        abort_unless($shipment->order_id === $order->id, 404);

        $validated = $request->validate([
            'courier' => ['required', 'string', 'max:80'],
            'method' => ['nullable', 'string', 'max:80'],
            'tracking_number' => ['required', 'string', 'max:80'],
            'tracking_url' => ['nullable', 'url', 'max:300'],
            'status' => ['required', 'in:'.implode(',', ShipmentService::STATUSES)],
            'shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'weight_grams' => ['nullable', 'numeric', 'min:0'],
            'estimated_delivery_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->shipments->update($shipment, $validated, auth()->user());
        $this->audit->record('orders.shipments.update', $shipment);

        return back()->with('success', 'Shipment and tracking updated.');
    }

    public function destroyShipment(Order $order, Shipment $shipment): RedirectResponse
    {
        abort_unless($shipment->order_id === $order->id, 404);

        $this->shipments->remove($shipment);

        return back()->with('success', 'Shipment removed. Order tracking fields were cleared.');
    }

    public function invoice(Order $order)
    {
        $order->load(['items', 'addresses', 'user']);
        $pdf = Pdf::loadView('admin.orders.invoice', compact('order'));

        return $pdf->download("invoice-{$order->number}.pdf");
    }

    public function packingSlip(Order $order)
    {
        $order->load(['items', 'addresses']);
        $pdf = Pdf::loadView('admin.orders.packing-slip', compact('order'));

        return $pdf->download("packing-slip-{$order->number}.pdf");
    }

    public function printOrders(Request $request): View
    {
        $ids = $this->resolveIds($request);

        $orders = Order::with(['items', 'addresses'])
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();

        abort_if($orders->isEmpty(), 404);

        return view('admin.orders.print', compact('orders'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->with(['user'])->orderBy('id');
        $ids = $this->resolveIds($request);

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'order_number', 'placed_at', 'customer', 'email', 'phone',
                'status', 'payment_status', 'fulfilment_status', 'channel',
                'subtotal', 'discount', 'shipping', 'tax', 'grand_total',
                'paid', 'refunded', 'payment_method', 'courier', 'tracking_number',
            ]);

            $query->chunk(200, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->number,
                        $order->created_at?->toDateTimeString(),
                        $order->user?->name ?? trim(($order->shippingAddress()?->first_name ?? '').' '.($order->shippingAddress()?->last_name ?? '')) ?: 'Guest',
                        $order->user?->email ?? $order->guest_email,
                        $order->guest_phone ?? $order->user?->phone,
                        $order->status?->value,
                        $order->payment_status?->value,
                        $order->fulfilment_status,
                        $order->order_channel,
                        $order->subtotal,
                        $order->discount_total,
                        $order->shipping_total,
                        $order->tax_total,
                        $order->grand_total,
                        $order->paid_amount,
                        $order->refunded_amount,
                        $order->payment_method,
                        $order->courier,
                        $order->tracking_number,
                    ]);
                }
            });

            fclose($handle);
        }, 'orders-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    private function filters(Request $request): array
    {
        return $request->only([
            'q', 'status', 'payment_status', 'channel', 'date_from', 'date_to',
            'min_total', 'max_total', 'view',
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        $filters = $this->filters($request);

        return Order::query()
            ->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn ($q, $status) => $q->where('payment_status', $status))
            ->when($filters['channel'] ?? null, fn ($q, $channel) => $q->where('order_channel', $channel))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->when($filters['min_total'] ?? null, fn ($q, $amount) => $q->where('grand_total', '>=', $amount))
            ->when($filters['max_total'] ?? null, fn ($q, $amount) => $q->where('grand_total', '<=', $amount))
            ->when($filters['view'] ?? null, fn ($q, $view) => $this->applySavedView($q, $view));
    }

    private function applySavedView(Builder $query, string $view): Builder
    {
        return match ($view) {
            'needs_action' => $query->whereIn('status', ['pending', 'confirmed', 'processing', 'packed']),
            'unpaid' => $query->whereIn('payment_status', ['unpaid', 'pending', 'failed'])
                ->whereNotIn('status', ['cancelled', 'returned']),
            'ready_to_ship' => $query->whereIn('status', ['processing', 'packed']),
            'in_transit' => $query->where('status', 'shipped'),
            'delivered' => $query->where('status', 'delivered'),
            'returns' => $query->whereIn('status', ['returned', 'partially_fulfilled']),
            default => $query,
        };
    }

    private function savedViews(array $filters): array
    {
        $current = $filters['view'] ?? 'all';

        return collect([
            'all' => 'All orders',
            'needs_action' => 'Needs action',
            'unpaid' => 'Unpaid',
            'ready_to_ship' => 'Ready to ship',
            'in_transit' => 'In transit',
            'delivered' => 'Delivered',
            'returns' => 'Returns',
        ])->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'url' => route('admin.orders.index', array_filter(array_merge($filters, ['view' => $key === 'all' ? null : $key, 'page' => null]))),
            'active' => $current === $key,
        ])->values()->all();
    }

    private function resolveIds(Request $request): array
    {
        $ids = $request->input('ids');

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        return collect($ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(100)
            ->values()
            ->all();
    }

    private function cancelOrder(Order $order, ?string $reason = null): RedirectResponse
    {
        try {
            $this->cancelWithRestock($order, $reason ?: 'Cancelled by staff.');
        } catch (Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Order cancelled and stock reconciled.');
    }

    /**
     * Cancel an order and undo stock exactly once. Reservations that were
     * never consumed are released; consumed stock is returned via the ledger.
     */
    private function cancelWithRestock(Order $order, ?string $reason = null): void
    {
        $order->refresh();

        if (! $order->isCancellable()) {
            throw new \RuntimeException("Order {$order->number} cannot be cancelled in its current state.");
        }

        DB::transaction(function () use ($order, $reason) {
            $order->loadMissing('items');
            $reservations = $order->stockReservations()->get();

            foreach ($order->items as $item) {
                if (! $item->product_variant_id) {
                    continue;
                }

                $saleWasConsumed = InventoryMovement::where('reference_type', Order::class)
                    ->where('reference_id', $order->id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->where('type', 'sale_out')
                    ->exists();

                if ($saleWasConsumed) {
                    $alreadyReturned = InventoryMovement::where('reference_type', Order::class)
                        ->where('reference_id', $order->id)
                        ->where('product_variant_id', $item->product_variant_id)
                        ->where('type', 'return_in')
                        ->where('reason', 'like', 'Order cancelled%')
                        ->exists();

                    $variant = ProductVariant::find($item->product_variant_id);

                    if (! $alreadyReturned && $variant) {
                        $this->inventory->restock(
                            $variant,
                            $item->quantity,
                            'Order cancelled',
                            auth()->user(),
                            $order,
                        );
                    }
                } else {
                    $reservation = $reservations->first(fn ($reservation) => $reservation->product_variant_id === $item->product_variant_id
                        && $reservation->released_at === null);

                    if ($reservation) {
                        $this->inventory->release($reservation);
                    }
                }
            }

            // Release anything left over (for example if the product was deleted).
            foreach ($reservations->whereNull('released_at') as $reservation) {
                $this->inventory->release($reservation);
            }

            $order->transitionTo(OrderStatus::Cancelled, auth()->user(), $reason);
        });

        $this->audit->record('orders.cancel', $order);
    }
}
