<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $collections = Collection::search($request->input('q'))->latest()->paginate(20);
        return view('admin.collections.index', compact('collections'));
    }

    public function create(): View
    {
        return view('admin.collections.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|string|max:300',
            'type' => 'required|in:manual,rule_based',
            'rules' => 'nullable|array',
            'match_condition' => 'required|in:all,any',
            'status' => 'required|in:active,inactive',
            'show_on_homepage' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $collection = Collection::create($validated);
        $this->audit->record('collections.create', $collection);
        return redirect()->route('admin.collections.index')->with('success', 'Collection created.');
    }

    public function show(Collection $collection)
    {
        return redirect()->route('admin.collections.edit', $collection);
    }

    public function edit(Collection $collection): View
    {
        return view('admin.collections.edit', compact('collection'));
    }

    public function update(Request $request, Collection $collection)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string',
            'banner_image' => 'nullable|string|max:300',
            'type' => 'required|in:manual,rule_based',
            'rules' => 'nullable|array',
            'match_condition' => 'required|in:all,any',
            'status' => 'required|in:active,inactive',
            'show_on_homepage' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $collection->update($validated);
        $this->audit->record('collections.update', $collection);
        return back()->with('success', 'Collection updated.');
    }

    public function destroy(Collection $collection)
    {
        $this->audit->record('collections.delete', $collection);
        $collection->delete();
        return redirect()->route('admin.collections.index')->with('success', 'Collection archived.');
    }
}
