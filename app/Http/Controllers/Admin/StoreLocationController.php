<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreLocation;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoreLocationController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $locations = StoreLocation::orderBy('sort_order')->paginate(20);

        return view('admin.store-locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('admin.store-locations.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateLocation($request);
        $location = StoreLocation::create($validated);
        $this->ensureSingleMain($location);
        $this->audit->record('store_locations.create', $location);

        return redirect()->route('admin.store-locations.index')->with('success', 'Store location created.');
    }

    public function show(StoreLocation $store_location)
    {
        return redirect()->route('admin.store-locations.edit', $store_location);
    }

    public function edit(StoreLocation $store_location): View
    {
        return view('admin.store-locations.edit', ['location' => $store_location]);
    }

    public function update(Request $request, StoreLocation $store_location)
    {
        $validated = $this->validateLocation($request, $store_location);
        $store_location->update($validated);
        $this->ensureSingleMain($store_location);
        $this->audit->record('store_locations.update', $store_location);

        return back()->with('success', 'Store location updated.');
    }

    public function destroy(StoreLocation $store_location)
    {
        $this->audit->record('store_locations.delete', $store_location);
        $store_location->delete();
        return redirect()->route('admin.store-locations.index')->with('success', 'Store location deleted.');
    }

    private function validateLocation(Request $request, ?StoreLocation $location = null): array
    {
        return $request->validate([
            'name'      => 'required|string|max:150',
            'slug'      => ['nullable', 'string', 'max:160', Rule::unique('store_locations', 'slug')->ignore($location?->id)],
            'address'   => 'required|string|max:300',
            'phone'     => 'nullable|string|max:30',
            'email'     => 'nullable|email|max:150',
            'whatsapp'  => 'nullable|string|max:30',
            'map_link'  => 'nullable|string|max:500',
            'latitude'  => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'hours'     => 'nullable|string|max:200',
            'is_main'   => 'sometimes|boolean',
            'status'    => 'required|in:active,inactive',
            'sort_order' => 'sometimes|integer|min:0',
        ]);
    }

    private function ensureSingleMain(StoreLocation $location): void
    {
        if ($location->is_main) {
            StoreLocation::where('id', '!=', $location->id)->update(['is_main' => false]);
        }
    }
}
