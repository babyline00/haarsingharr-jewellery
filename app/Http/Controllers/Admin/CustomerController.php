<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $customers = User::customers()
            ->when($request->input('q'), function ($q, $term) {
                $q->where('email', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"]);
            })
            ->latest()
            ->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer): View
    {
        $customer->load(['orders', 'addresses', 'reviews', 'wishlist.items']);
        return view('admin.customers.show', compact('customer'));
    }

    public function update(Request $request, User $customer)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,deactivated',
            'marketing_consent' => 'sometimes|boolean',
            'internal_notes' => 'nullable|string|max:2000',
        ]);
        $this->audit->record('customers.update', $customer, $customer->getAttributes(), $validated);
        $customer->update($validated);
        return back()->with('success', 'Customer updated.');
    }

    public function destroy(User $customer)
    {
        $this->audit->record('customers.delete', $customer);
        $customer->forceFill(['status' => UserStatus::Deactivated, 'data_deleted_at' => now()])->save();
        return back()->with('success', 'Customer deactivated.');
    }
}
