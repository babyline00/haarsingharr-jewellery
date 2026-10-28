<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\StoreSetting;
use App\Services\AuditService;
use App\Services\ShipmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly ShipmentService $shipments,
    ) {}

    public function index(Request $request): View
    {
        $settings = [];
        foreach ($this->schema() as $key => $field) {
            $setting = StoreSetting::where('key', $key)->first();
            $settings[$key] = [
                'key' => $key,
                'label' => $field['label'],
                'type' => $field['type'],
                'value' => $setting?->value ?? ($field['default'] ?? null),
            ];
        }

        $filters = $request->only(['q', 'status', 'courier', 'date_from', 'date_to']);

        $shipments = $this->filteredShipments($request)
            ->with('order.user')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $codPending = Order::query()
            ->with('user')
            ->where('payment_method', 'cod')
            ->whereIn('payment_status', ['unpaid', 'pending'])
            ->where('status', 'delivered')
            ->latest()
            ->get();

        return view('admin.shipping.index', [
            'settings' => $settings,
            'shipments' => $shipments,
            'filters' => $filters,
            'zones' => ShippingZone::with('rates')->orderBy('sort_order')->orderBy('name')->get(),
            'couriers' => Shipment::query()->whereNotNull('courier')->distinct()->orderBy('courier')->pluck('courier'),
            'statuses' => ShipmentService::STATUSES,
            'codOrders' => $codPending->take(8),
            'stats' => [
                'awaiting' => Shipment::where('status', 'pending')->count(),
                'in_transit' => Shipment::whereIn('status', ['picked_up', 'in_transit', 'out_for_delivery'])->count(),
                'delivered_today' => Shipment::where('status', 'delivered')->whereDate('delivered_at', today())->count(),
                'cod_orders' => $codPending->count(),
                'cod_value' => (float) $codPending->sum('grand_total'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['shipping' => 'array']);

        $input = $request->input('shipping', []);

        foreach ($this->schema() as $key => $field) {
            $value = $input[$key] ?? null;

            if ($field['type'] === 'boolean') {
                $value = (bool) $value;
            } elseif ($field['type'] === 'number') {
                $value = $value === null || $value === '' ? null : (float) $value;
            } else {
                $value = is_string($value) ? trim($value) : $value;
            }

            StoreSetting::set($key, $value, 'shipping');
        }

        $this->audit->record('shipping.update', StoreSetting::class);

        return back()->with('success', 'Fallback shipping settings saved.');
    }

    public function storeZone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'countries' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $zone = ShippingZone::create([
            'name' => $validated['name'],
            'code' => $this->uniqueZoneCode($validated['name']),
            'countries' => $this->countryList($validated['countries'] ?? null),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->record('shipping.zones.create', $zone);

        return back()->with('success', "Shipping zone “{$zone->name}” created.");
    }

    public function updateZone(Request $request, ShippingZone $zone): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'countries' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $previous = $zone->only(['name', 'countries', 'is_active', 'sort_order']);

        $zone->update([
            'name' => $validated['name'],
            'countries' => $this->countryList($validated['countries'] ?? null),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->record('shipping.zones.update', $zone, $previous, $zone->only(array_keys($previous)));

        return back()->with('success', 'Shipping zone updated.');
    }

    public function destroyZone(ShippingZone $zone): RedirectResponse
    {
        $name = $zone->name;
        $zone->delete();

        $this->audit->record('shipping.zones.delete', $zone, ['name' => $name]);

        return back()->with('success', "Shipping zone “{$name}” removed.");
    }

    public function storeRate(Request $request, ShippingZone $zone): RedirectResponse
    {
        $validated = $this->validateRate($request);

        $rate = $zone->rates()->create([
            'name' => $validated['name'],
            'method' => $validated['method'],
            'min_order_total' => $validated['min_order_total'] ?? null,
            'rate' => $validated['rate'],
            'free_over' => $validated['free_over'] ?? null,
            'min_weight_grams' => $validated['min_weight_grams'] ?? null,
            'max_weight_grams' => $validated['max_weight_grams'] ?? null,
            'eta_min_days' => $validated['eta_min_days'] ?? null,
            'eta_max_days' => $validated['eta_max_days'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->record('shipping.rates.create', $rate);

        return back()->with('success', 'Shipping rate added.');
    }

    public function updateRate(Request $request, ShippingRate $rate): RedirectResponse
    {
        $validated = $this->validateRate($request);
        $previous = $rate->only(array_keys($validated));

        $rate->update([
            'name' => $validated['name'],
            'method' => $validated['method'],
            'min_order_total' => $validated['min_order_total'] ?? null,
            'rate' => $validated['rate'],
            'free_over' => $validated['free_over'] ?? null,
            'min_weight_grams' => $validated['min_weight_grams'] ?? null,
            'max_weight_grams' => $validated['max_weight_grams'] ?? null,
            'eta_min_days' => $validated['eta_min_days'] ?? null,
            'eta_max_days' => $validated['eta_max_days'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->audit->record('shipping.rates.update', $rate, $previous, $rate->only(array_keys($previous)));

        return back()->with('success', 'Shipping rate updated.');
    }

    public function destroyRate(ShippingRate $rate): RedirectResponse
    {
        $name = $rate->name;
        $rate->delete();

        $this->audit->record('shipping.rates.delete', $rate, ['name' => $name]);

        return back()->with('success', "Rate “{$name}” removed.");
    }

    public function updateShipment(Request $request, Shipment $shipment): RedirectResponse
    {
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
        $this->audit->record('shipping.shipments.update', $shipment);

        return back()->with('success', 'Shipment updated.');
    }

    public function destroyShipment(Shipment $shipment): RedirectResponse
    {
        $this->shipments->remove($shipment);

        return back()->with('success', 'Shipment removed and tracking cleared.');
    }

    public function label(Shipment $shipment)
    {
        $shipment->load(['order.items', 'order.addresses']);

        $pdf = Pdf::loadView('admin.shipping.label', compact('shipment'))
            ->setPaper('a6', 'portrait');

        return $pdf->download("label-{$shipment->order?->number}-{$shipment->id}.pdf");
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    private function filteredShipments(Request $request): Builder
    {
        $filters = $request->only(['q', 'status', 'courier', 'date_from', 'date_to']);

        return Shipment::query()
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($inner) use ($term) {
                    $inner->where('tracking_number', 'like', "%{$term}%")
                        ->orWhere('courier', 'like', "%{$term}%")
                        ->orWhereHas('order', fn ($order) => $order->where('number', 'like', "%{$term}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['courier'] ?? null, fn ($q, $courier) => $q->where('courier', $courier))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
    }

    private function validateRate(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'method' => ['required', 'string', 'max:60'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['required', 'numeric', 'min:0'],
            'free_over' => ['nullable', 'numeric', 'min:0'],
            'min_weight_grams' => ['nullable', 'numeric', 'min:0'],
            'max_weight_grams' => ['nullable', 'numeric', 'min:0'],
            'eta_min_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'eta_max_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        if (isset($validated['max_weight_grams'], $validated['min_weight_grams'])
            && (float) $validated['max_weight_grams'] > 0
            && (float) $validated['max_weight_grams'] < (float) $validated['min_weight_grams']) {
            throw ValidationException::withMessages([
                'max_weight_grams' => 'Maximum weight must be greater than the minimum weight.',
            ]);
        }

        if (isset($validated['eta_max_days'], $validated['eta_min_days'])
            && $validated['eta_max_days'] < $validated['eta_min_days']) {
            throw ValidationException::withMessages([
                'eta_max_days' => 'Maximum ETA must be greater than the minimum ETA.',
            ]);
        }

        return $validated;
    }

    private function uniqueZoneCode(string $name): string
    {
        $base = Str::slug($name) ?: 'zone';
        $code = $base;
        $suffix = 2;

        while (ShippingZone::withTrashed()->where('code', $code)->exists()) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }

    private function countryList(?string $countries): ?array
    {
        if (! $countries) {
            return null;
        }

        return collect(explode(',', $countries))
            ->map(fn ($country) => strtoupper(trim($country)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function schema(): array
    {
        return [
            'default_method' => ['label' => 'Default shipping method', 'type' => 'text', 'default' => config('storefront.shipping.default_method')],
            'free_shipping_threshold' => ['label' => 'Free shipping over', 'type' => 'number', 'default' => config('storefront.shipping.free_shipping_threshold')],
            'flat_rate' => ['label' => 'Standard flat rate', 'type' => 'number', 'default' => config('storefront.shipping.flat_rate')],
            'express_rate' => ['label' => 'Express rate', 'type' => 'number', 'default' => config('storefront.shipping.express_rate')],
            'cod_eligible' => ['label' => 'Cash on delivery eligible', 'type' => 'boolean', 'default' => config('storefront.shipping.cod_eligible')],
            'handling_days' => ['label' => 'Handling days', 'type' => 'number', 'default' => config('storefront.shipping.handling_days')],
        ];
    }
}
