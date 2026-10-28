<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Services\AuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CouponController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status', 'type']);

        $coupons = Coupon::query()
            ->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->withSum('redemptions as redeemed_total', 'discount_applied')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.coupons.index', [
            'coupons' => $coupons,
            'filters' => $filters,
            'stats'   => $this->indexStats(),
        ]);
    }

    public function show(Coupon $coupon): View
    {
        $redemptions = $coupon->redemptions()
            ->with(['order', 'user'])
            ->latest()
            ->paginate(15);

        $stats = $this->usageStats($coupon);

        return view('admin.coupons.show', compact('coupon', 'redemptions', 'stats'));
    }

    public function create(): View
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateCoupon($request);
        $coupon = Coupon::create($validated);
        $this->audit->record('coupons.create', $coupon);
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $this->validateCoupon($request, $coupon->id);
        $previous = $coupon->only(array_keys($validated));
        $coupon->update($validated);
        $this->audit->record('coupons.update', $coupon, $previous, $validated);
        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->audit->record('coupons.delete', $coupon);
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon archived.');
    }

    // --------------------------------------------------------------------------
    // Bulk marketing code generation
    // --------------------------------------------------------------------------
    public function generateForm(): View
    {
        return view('admin.coupons.generate');
    }

    public function generateStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'prefix'      => ['required', 'string', 'max:16', 'regex:/^[A-Z0-9]+$/i'],
            'quantity'    => ['required', 'integer', 'min:1', 'max:100'],
            'code_length' => ['nullable', 'integer', 'min:4', 'max:12'],

            'type'                       => 'required|in:percentage,fixed,free_shipping,product_specific,category_specific',
            'value'                      => 'required|numeric|min:0',
            'min_order_value'            => 'nullable|numeric|min:0',
            'max_discount_amount'        => 'nullable|numeric|min:0',
            'usage_limit_total'          => 'nullable|integer|min:1',
            'usage_limit_per_customer'   => 'nullable|integer|min:1',
            'first_order_only'           => 'sometimes|boolean',
            'exclude_sale_items'         => 'sometimes|boolean',
            'starts_at'                  => 'nullable|date',
            'expires_at'                 => 'nullable|date|after_or_equal:starts_at',
            'status'                     => 'required|in:active,scheduled,expired,disabled',
        ]);

        $prefix = Str::upper(Str::ascii((string) $validated['prefix']));
        $length = $validated['code_length'] ?? 5;

        $template = collect($validated)->except([
            'prefix', 'quantity', 'code_length',
        ])->all();

        $created = [];

        DB::transaction(function () use ($validated, $prefix, $length, $template, &$created) {
            while (count($created) < $validated['quantity']) {
                $code = $prefix.'-'.Str::upper(Str::random($length));

                if (Coupon::withTrashed()->where('code', $code)->exists()) {
                    continue;
                }

                $created[] = Coupon::create(array_merge($template, ['code' => $code]));
            }
        });

        $this->audit->record('coupons.generate', $created[0] ?? null, [], [
            'prefix'   => $prefix,
            'quantity' => count($created),
        ]);

        return redirect()->route('admin.coupons.index')
            ->with('success', count($created).' coupon code(s) generated with prefix '.$prefix.'.');
    }

    // --------------------------------------------------------------------------
    // Bulk actions + export
    // --------------------------------------------------------------------------
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:100'],
            'ids.*'  => ['integer', 'exists:coupons,id'],
            'action' => ['required', 'in:activate,disable,enable,delete,export'],
        ]);

        if ($validated['action'] === 'export') {
            return redirect()->route('admin.coupons.export', ['ids' => implode(',', $validated['ids'])]);
        }

        $coupons = Coupon::whereIn('id', $validated['ids'])->get();

        foreach ($coupons as $coupon) {
            match ($validated['action']) {
                'activate' => $coupon->update(['status' => 'active']),
                'enable'   => $coupon->update(['status' => $coupon->status === 'disabled' ? 'active' : $coupon->status]),
                'disable'  => $coupon->update(['status' => 'disabled']),
                'delete'   => (function () use ($coupon) {
                    $this->audit->record('coupons.bulk.delete', $coupon);
                    $coupon->delete();
                })(),
            };
        }

        $this->audit->record('coupons.bulk.update', null, [], [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', count($validated['ids']).' coupon(s) updated.');
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->orderBy('id');

        $idsParam = $request->input('ids');
        $ids = collect(is_string($idsParam) ? explode(',', $idsParam) : ($idsParam ?? []))
            ->filter(fn ($id) => is_numeric($id))->map(fn ($id) => (int) $id)
            ->unique()->take(100)->values()->all();

        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'code', 'type', 'value', 'min_order_value', 'max_discount_amount',
                'usage_limit_total', 'usage_limit_per_customer', 'used_count',
                'first_order_only', 'exclude_sale_items', 'starts_at', 'expires_at',
                'status', 'created_at',
            ]);

            $query->chunk(200, function ($coupons) use ($handle) {
                foreach ($coupons as $coupon) {
                    fputcsv($handle, [
                        $coupon->code,
                        $coupon->type,
                        $coupon->value,
                        $coupon->min_order_value,
                        $coupon->max_discount_amount,
                        $coupon->usage_limit_total,
                        $coupon->usage_limit_per_customer,
                        $coupon->used_count,
                        $coupon->first_order_only ? 'yes' : 'no',
                        $coupon->exclude_sale_items ? 'yes' : 'no',
                        $coupon->starts_at?->toDateTimeString(),
                        $coupon->expires_at?->toDateTimeString(),
                        $coupon->status,
                        $coupon->created_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        }, 'coupons-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    private function validateCoupon(Request $request, ?int $ignore = null): array
    {
        $codeRule = 'required|string|max:40|unique:coupons,code';

        if ($ignore) {
            $codeRule .= ','.$ignore;
        }

        return $request->validate([
            'code'            => $codeRule,
            'type'            => 'required|in:percentage,fixed,free_shipping,product_specific,category_specific',
            'value'           => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'first_order_only' => 'sometimes|boolean',
            'exclude_sale_items' => 'sometimes|boolean',
            'starts_at'       => 'nullable|date',
            'expires_at'      => 'nullable|date|after_or_equal:starts_at',
            'status'          => 'required|in:active,scheduled,expired,disabled',
        ]);
    }

    private function filteredQuery(Request $request): Builder
    {
        return Coupon::query()
            ->search($request->input('q'))
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->input('type'), fn ($query, $type) => $query->where('type', $type));
    }

    private function indexStats(): array
    {
        return [
            'active'      => Coupon::where('status', 'active')->count(),
            'scheduled'   => Coupon::where('status', 'scheduled')->count(),
            'expiring'    => Coupon::active()->whereDate('expires_at', '<=', now()->addDays(7))->count(),
            'redeemed'    => $this->totalDiscount(),
        ];
    }

    private function totalDiscount(): float
    {
        return (float) CouponRedemption::query()
            ->whereIn('coupon_id', Coupon::withTrashed()->select('id'))
            ->sum('discount_applied');
    }

    private function usageStats(Coupon $coupon): array
    {
        // Qualified because the avg query joins orders (which also has a coupon_id column).
        $redemptions = CouponRedemption::where('coupon_redemptions.coupon_id', $coupon->id);

        return [
            'used_count'        => $coupon->used_count,
            'limit'             => $coupon->usage_limit_total,
            'unique_customers'  => (clone $redemptions)->distinct('user_id')->count('user_id'),
            'total_discount'    => (float) (clone $redemptions)->sum('discount_applied'),
            'avg_order_value'   => (float) (clone $redemptions)
                ->join('orders', 'orders.id', '=', 'coupon_redemptions.order_id')
                ->avg('orders.subtotal'),
            'first_redemption'  => (clone $redemptions)->min('created_at'),
            'last_redemption'   => (clone $redemptions)->max('created_at'),
        ];
    }
}
