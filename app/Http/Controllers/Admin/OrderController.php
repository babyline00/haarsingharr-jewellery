<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\AuditService;
use Illuminate\Http\Request;
use App\Enums\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $orders = Order::with(['user', 'items'])
            ->search($request->input('q'))
            ->when($request->input('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->input('payment_status'), fn ($q, $s) => $q->where('payment_status', $s))
            ->latest()
            ->paginate(20);

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => $request->only(['q', 'status', 'payment_status']),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'addresses', 'statusHistory.actor', 'payments.events', 'shipments']);
        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,processing,packed,shipped,delivered,cancelled,returned,partially_fulfilled',
            'note' => 'nullable|string|max:500',
        ]);

        $newStatus = OrderStatus::from($validated['status']);
        if (! $order->status->canTransitionTo($newStatus)) {
            return back()->with('error', "Cannot transition from {$order->status->label()} to {$newStatus->label()}.");
        }

        $order->transitionTo($newStatus, auth()->user(), $validated['note']);
        $this->audit->record('orders.status.update', $order,
            ['status' => $order->getOriginal('status')],
            ['status' => $newStatus->value],
        );

        return back()->with('success', 'Order status updated.');
    }

    public function storeShipment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'courier' => 'required|string|max:80',
            'method'  => 'nullable|string|max:80',
            'tracking_number' => 'required|string|max:80',
            'tracking_url' => 'nullable|url|max:300',
            'shipping_cost' => 'nullable|numeric|min:0',
            'weight_grams' => 'nullable|numeric|min:0',
        ]);

        $shipment = Shipment::create(array_merge($validated, [
            'order_id' => $order->id,
            'status' => 'picked_up',
            'shipped_at' => now(),
        ]));

        $order->update([
            'courier' => $validated['courier'],
            'tracking_number' => $validated['tracking_number'],
            'tracking_url' => $validated['tracking_url'] ?? null,
            'shipped_at' => now(),
        ]);

        if ($order->status === OrderStatus::Packed) {
            $order->transitionTo(OrderStatus::Shipped, auth()->user());
        }

        $this->audit->record('orders.shipments.create', $shipment);
        return back()->with('success', 'Shipment recorded and order marked shipped.');
    }

    public function invoice(Order $order)
    {
        $order->load(['items', 'addresses', 'user']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.orders.invoice', compact('order'));
        return $pdf->download("invoice-{$order->number}.pdf");
    }

    public function packingSlip(Order $order)
    {
        $order->load(['items', 'addresses']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.orders.packing-slip', compact('order'));
        return $pdf->download("packing-slip-{$order->number}.pdf");
    }
}
