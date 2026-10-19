<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $coupons = Coupon::search($request->input('q'))->latest()->paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:40|unique:coupons,code',
            'type' => 'required|in:percentage,fixed,free_shipping,product_specific,category_specific',
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'first_order_only' => 'sometimes|boolean',
            'exclude_sale_items' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|in:active,scheduled,expired,disabled',
        ]);
        $coupon = Coupon::create($validated);
        $this->audit->record('coupons.create', $coupon);
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function show(Coupon $coupon)
    {
        return redirect()->route('admin.coupons.edit', $coupon);
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:40|unique:coupons,code,'.$coupon->id,
            'type' => 'required|in:percentage,fixed,free_shipping,product_specific,category_specific',
            'value' => 'required|numeric|min:0',
            'min_order_value' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit_total' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:1',
            'first_order_only' => 'sometimes|boolean',
            'exclude_sale_items' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|in:active,scheduled,expired,disabled',
        ]);
        $coupon->update($validated);
        $this->audit->record('coupons.update', $coupon);
        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $this->audit->record('coupons.delete', $coupon);
        $coupon->delete();
        return redirect()->route('admin.coupons.index')->with('success', 'Coupon archived.');
    }
}
