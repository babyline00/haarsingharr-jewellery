<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageSectionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $sections = HomepageSection::orderBy('sort_order')->paginate(30);

        return view('admin.homepage.index', compact('sections'));
    }

    public function create(): View
    {
        return view('admin.homepage.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateSection($request);
        $validated['configuration'] = $this->decodeJson($request->input('configuration'));

        $section = HomepageSection::create($validated);
        $this->audit->record('homepage.create', $section);

        return redirect()->route('admin.homepage-sections.index')->with('success', 'Homepage section created.');
    }

    public function show(HomepageSection $homepage_section)
    {
        return redirect()->route('admin.homepage-sections.edit', $homepage_section);
    }

    public function edit(HomepageSection $homepage_section): View
    {
        return view('admin.homepage.edit', ['section' => $homepage_section]);
    }

    public function update(Request $request, HomepageSection $homepage_section)
    {
        $validated = $this->validateSection($request);
        $validated['configuration'] = $this->decodeJson($request->input('configuration'));

        $homepage_section->update($validated);
        $this->audit->record('homepage.update', $homepage_section);

        return back()->with('success', 'Homepage section updated.');
    }

    public function destroy(HomepageSection $homepage_section)
    {
        $this->audit->record('homepage.delete', $homepage_section);
        $homepage_section->delete();
        return redirect()->route('admin.homepage-sections.index')->with('success', 'Homepage section deleted.');
    }

    private function validateSection(Request $request): array
    {
        return $request->validate([
            'key'          => 'required|string|max:80',
            'title'        => 'nullable|string|max:200',
            'subtitle'     => 'nullable|string|max:300',
            'content'      => 'nullable|string',
            'view'         => 'nullable|string|max:120',
            'sort_order'   => 'sometimes|integer|min:0',
            'is_published' => 'sometimes|boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after_or_equal:starts_at',
        ]);
    }

    private function decodeJson(?string $json): ?array
    {
        if (! $json) {
            return null;
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}
