<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $variants = ProductVariant::with('product')
            ->when($request->input('q'), function ($q, $term) {
                $q->where('sku', 'like', "%{$term}%")
                  ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            })
            ->when($request->input('filter') === 'low_stock', function ($q) {
                $q->whereColumn('stock_quantity', '<=', 'COALESCE(low_stock_threshold, 5)')
                  ->whereColumn('stock_quantity', '>', 'reserved_stock');
            })
            ->when($request->input('filter') === 'out_of_stock', function ($q) {
                $q->whereColumn('stock_quantity', '<=', 'reserved_stock');
            })
            ->latest()
            ->paginate(20);

        return view('admin.inventory.index', [
            'variants' => $variants,
            'filters' => $request->only(['q', 'filter']),
        ]);
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'new_stock'         => 'required|integer|min:0|max:1000000',
            'reason'            => ['required', 'string', Rule::in([
                'physical_count', 'damaged', 'lost', 'received_shipment',
                'returned', 'transfer_in', 'transfer_out', 'manual', 'correction',
            ])],
            'notes'             => 'nullable|string|max:500',
        ]);

        $variant = ProductVariant::findOrFail($validated['product_variant_id']);
        $movement = $this->inventoryService->adjust(
            $variant,
            (int) $validated['new_stock'],
            $validated['reason'],
            auth()->user(),
            $validated['notes'] ?? null,
        );
        $this->audit->record('inventory.adjust', $variant, null, $validated);

        return back()->with('success',
            "Stock updated to {$validated['new_stock']} (change {$movement->quantity_change}).");
    }

    public function movements(Request $request): View
    {
        $movements = InventoryMovement::with(['productVariant.product', 'actor'])
            ->when($request->input('variant'), fn ($q, $id) => $q->where('product_variant_id', $id))
            ->when($request->input('type'), fn ($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(50);

        return view('admin.inventory.movements', compact('movements'));
    }
}
