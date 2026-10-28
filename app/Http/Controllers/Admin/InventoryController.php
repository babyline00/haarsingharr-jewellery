<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryAlert;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Services\AuditService;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class InventoryController extends Controller
{
    private const REASONS = [
        'physical_count', 'damaged', 'lost', 'received_shipment',
        'returned', 'transfer_in', 'transfer_out', 'manual', 'correction',
    ];

    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->input('tab', 'all');
        if ($tab === 'low_stock') {
            $tab = 'low';
        } elseif ($tab === 'out_of_stock') {
            $tab = 'out';
        }
        if (! in_array($tab, ['all', 'low', 'out'], true)) {
            $tab = 'all';
        }

        $variants = ProductVariant::with('product')
            ->when($request->input('q'), function ($query, $term) {
                $query->where(function ($sub) use ($term) {
                    $sub->where('sku', 'like', "%{$term}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$term}%"));
                });
            })
            ->when($tab === 'low', function ($query) {
                $query->whereRaw('(stock_quantity - reserved_stock - safety_stock) > 0')
                    ->whereRaw('(stock_quantity - reserved_stock - safety_stock) <= COALESCE(low_stock_threshold, 5)');
            })
            ->when($tab === 'out', function ($query) {
                $query->whereRaw('(stock_quantity - reserved_stock - safety_stock) <= 0');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => ProductVariant::count(),
            'units' => (int) ProductVariant::sum('stock_quantity'),
            'retail' => (float) ProductVariant::sum(DB::raw('stock_quantity * COALESCE(sale_price, regular_price, 0)')),
            'cost' => (float) ProductVariant::sum(DB::raw('stock_quantity * COALESCE(cost_price, 0)')),
            'low' => ProductVariant::whereRaw('(stock_quantity - reserved_stock - safety_stock) > 0')
                ->whereRaw('(stock_quantity - reserved_stock - safety_stock) <= COALESCE(low_stock_threshold, 5)')
                ->count(),
            'out' => ProductVariant::whereRaw('(stock_quantity - reserved_stock - safety_stock) <= 0')->count(),
        ];

        $alerts = InventoryAlert::with('productVariant.product')
            ->where('is_resolved', false)
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.inventory.index', [
            'variants' => $variants,
            'filters' => ['q' => $request->input('q'), 'tab' => $tab],
            'stats' => $stats,
            'alerts' => $alerts,
            'reasons' => $this->reasonOptions(),
        ]);
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'new_stock' => 'required|integer|min:0|max:1000000',
            'reason' => ['required', 'string', Rule::in(self::REASONS)],
            'notes' => 'nullable|string|max:500',
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

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => 'integer|exists:product_variants,id',
            'mode' => 'required|in:set,adjust',
            'value' => 'required|integer|min:-1000000|max:1000000',
            'reason' => ['required', 'string', Rule::in(self::REASONS)],
            'notes' => 'nullable|string|max:500',
        ]);

        if ($data['mode'] === 'set' && $data['value'] < 0) {
            return back()->with('error', 'A stock level cannot be negative.');
        }

        $variants = ProductVariant::whereIn('id', $data['ids'])->get();
        $updated = 0;

        foreach ($variants as $variant) {
            $target = $data['mode'] === 'set'
                ? (int) $data['value']
                : max(0, $variant->stock_quantity + (int) $data['value']);

            $this->inventoryService->adjust(
                $variant,
                $target,
                $data['reason'],
                $request->user(),
                $data['notes'] ?? null,
            );

            $this->audit->record('inventory.bulk_adjust', $variant, null, [
                'mode' => $data['mode'],
                'value' => (int) $data['value'],
                'new_stock' => $target,
                'reason' => $data['reason'],
            ]);

            $updated++;
        }

        return back()->with('success', "{$updated} stock record(s) updated.");
    }

    public function resolveAlert(InventoryAlert $alert): RedirectResponse
    {
        $alert->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        $this->audit->record('inventory.alert.resolve', $alert->productVariant, null, [
            'alert_id' => $alert->id,
        ]);

        return back()->with('success', 'Alert acknowledged.');
    }

    public function movements(Request $request): View
    {
        $movements = InventoryMovement::with(['productVariant.product', 'actor'])
            ->when($request->input('variant'), fn ($q, $id) => $q->where('product_variant_id', $id))
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('q'), function ($query, $term) {
                $query->where(function ($sub) use ($term) {
                    $sub->whereHas('productVariant', fn ($variant) => $variant->where('sku', 'like', "%{$term}%"))
                        ->orWhereHas('productVariant.product', fn ($product) => $product->where('name', 'like', "%{$term}%"));
                });
            })
            ->latest()
            ->paginate(50)
            ->withQueryString();

        $variant = null;
        if ($request->filled('variant')) {
            $variant = ProductVariant::with('product')->find($request->input('variant'));
        }

        return view('admin.inventory.movements', [
            'movements' => $movements,
            'variant' => $variant,
            'filters' => $request->only(['q', 'type', 'variant']),
            'types' => [
                'adjustment', 'purchase', 'return_in', 'sale_out',
                'reservation', 'release', 'restock', 'damage', 'transfer',
            ],
        ]);
    }

    public function export(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'product', 'sku', 'on_hand', 'reserved', 'safety_stock',
                'available', 'low_stock_threshold', 'retail_value',
            ]);

            ProductVariant::with('product')->orderBy('sku')->chunk(500, function ($variants) use ($handle) {
                foreach ($variants as $variant) {
                    fputcsv($handle, [
                        $variant->product?->name,
                        $variant->sku,
                        $variant->stock_quantity,
                        $variant->reserved_stock,
                        $variant->safety_stock,
                        $variant->availableStock(),
                        $variant->low_stock_threshold,
                        (float) $variant->availableStock() * (float) ($variant->sale_price ?: $variant->regular_price),
                    ]);
                }
            });

            fclose($handle);
        }, 'inventory-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importForm(): View
    {
        return view('admin.inventory.import', [
            'importErrors' => session('inventory_import_errors', []),
        ]);
    }

    public function importStore(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:4096',
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'The CSV could not be read.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return back()->with('error', 'The CSV is empty.');
        }

        $header = array_map(function ($value) {
            $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);

            return Str::snake(trim($value));
        }, $header);

        if (! in_array('sku', $header, true) || ! in_array('new_stock', $header, true)) {
            fclose($handle);

            return back()->with('error', 'The CSV must contain "sku" and "new_stock" columns.');
        }

        $updated = 0;
        $errors = [];
        $line = 1;
        $processed = 0;

        while (($values = fgetcsv($handle)) !== false) {
            $line++;

            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }

            if (++$processed > 1000) {
                $errors[] = ['line' => $line, 'message' => 'Import stopped at 1,000 rows. Split the file and try again.'];
                break;
            }

            $row = array_combine(
                $header,
                array_pad(array_slice($values, 0, count($header)), count($header), null),
            );
            if (! is_array($row)) {
                continue;
            }

            $sku = trim((string) ($row['sku'] ?? ''));
            $newStock = trim((string) ($row['new_stock'] ?? ''));
            $reason = trim((string) ($row['reason'] ?? 'physical_count')) ?: 'physical_count';

            if ($sku === '' || ! is_numeric($newStock) || (int) $newStock < 0) {
                $errors[] = ['line' => $line, 'message' => 'A valid SKU and non-negative new_stock are required.'];

                continue;
            }

            if (! in_array($reason, self::REASONS, true)) {
                $errors[] = ['line' => $line, 'message' => "Unknown reason \"{$reason}\"."];

                continue;
            }

            $variant = ProductVariant::where('sku', $sku)->first();
            if (! $variant) {
                $errors[] = ['line' => $line, 'message' => "SKU {$sku} was not found."];

                continue;
            }

            try {
                $this->inventoryService->adjust(
                    $variant,
                    (int) $newStock,
                    $reason,
                    $request->user(),
                    trim((string) ($row['notes'] ?? '')) ?: 'Stock CSV import.',
                );
                $this->audit->record('inventory.import', $variant, null, [
                    'line' => $line,
                    'new_stock' => (int) $newStock,
                    'reason' => $reason,
                ]);
                $updated++;
            } catch (Throwable $e) {
                $errors[] = ['line' => $line, 'message' => $e->getMessage()];
            }
        }

        fclose($handle);

        session()->flash('inventory_import_errors', $errors);

        $message = "{$updated} stock row(s) applied.";
        if ($errors !== []) {
            $message .= ' '.count($errors).' row(s) were skipped — review the errors below.';
        }

        return redirect()->route('admin.inventory.import')
            ->with($errors === [] ? 'success' : 'error', $message);
    }

    /**
     * @return array<string, string>
     */
    private function reasonOptions(): array
    {
        return [
            'physical_count' => 'Physical count',
            'received_shipment' => 'Received shipment',
            'damaged' => 'Damaged',
            'lost' => 'Lost',
            'returned' => 'Returned',
            'transfer_in' => 'Transfer in',
            'transfer_out' => 'Transfer out',
            'correction' => 'Correction',
            'manual' => 'Manual adjustment',
        ];
    }
}
