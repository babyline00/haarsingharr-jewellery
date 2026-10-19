<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()->addresses()->get();
        return view('account.addresses.index', compact('addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => 'nullable|string|max:30',
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'phone_country' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:200',
            'address_line_2' => 'nullable|string|max:200',
            'landmark' => 'nullable|string|max:200',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:60',
            'country' => 'required|string|size:2',
            'postal_code' => 'nullable|string|max:10',
            'is_default_shipping' => 'sometimes|boolean',
            'is_default_billing' => 'sometimes|boolean',
        ]);

        $address = $request->user()->addresses()->create($validated);

        if (! empty($validated['is_default_shipping'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default_shipping' => false]);
        }
        if (! empty($validated['is_default_billing'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default_billing' => false]);
        }

        return back()->with('success', 'Address added.');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorize('update', $address);
        $address->update($request->validate([
            'label' => 'nullable|string|max:30',
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'phone_country' => 'required|string|max:5',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:200',
            'address_line_2' => 'nullable|string|max:200',
            'landmark' => 'nullable|string|max:200',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:60',
            'country' => 'required|string|size:2',
            'postal_code' => 'nullable|string|max:10',
        ]));
        return back()->with('success', 'Address updated.');
    }

    public function destroy(Address $address): RedirectResponse
    {
        $this->authorize('delete', $address);
        $address->delete();
        return back()->with('success', 'Address deleted.');
    }

    public function fill(Address $address)
    {
        $this->authorize('view', $address);
        return response()->json($address);
    }
}
