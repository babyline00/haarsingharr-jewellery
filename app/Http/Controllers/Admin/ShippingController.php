<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\StoreSetting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $settings = [];
        foreach ($this->schema() as $key => $field) {
            $setting = StoreSetting::where('key', $key)->first();
            $settings[$key] = [
                'key'   => $key,
                'label' => $field['label'],
                'type'  => $field['type'],
                'value' => $setting?->value ?? ($field['default'] ?? null),
            ];
        }

        $shipments = Shipment::with('order')
            ->latest()
            ->paginate(20);

        return view('admin.shipping.index', compact('settings', 'shipments'));
    }

    public function store(Request $request)
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

        return back()->with('success', 'Shipping settings saved.');
    }

    private function schema(): array
    {
        return [
            'default_method'          => ['label' => 'Default shipping method', 'type' => 'text', 'default' => config('storefront.shipping.default_method')],
            'free_shipping_threshold' => ['label' => 'Free shipping over', 'type' => 'number', 'default' => config('storefront.shipping.free_shipping_threshold')],
            'flat_rate'               => ['label' => 'Standard flat rate', 'type' => 'number', 'default' => config('storefront.shipping.flat_rate')],
            'express_rate'            => ['label' => 'Express rate', 'type' => 'number', 'default' => config('storefront.shipping.express_rate')],
            'cod_eligible'            => ['label' => 'Cash on delivery eligible', 'type' => 'boolean', 'default' => config('storefront.shipping.cod_eligible')],
            'handling_days'           => ['label' => 'Handling days', 'type' => 'number', 'default' => config('storefront.shipping.handling_days')],
        ];
    }
}
