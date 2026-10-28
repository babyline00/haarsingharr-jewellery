<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageSection;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomepageSectionController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $sections = HomepageSection::orderBy('sort_order')
            ->orderBy('id')
            ->paginate(30);

        return view('admin.homepage.index', [
            'sections' => $sections,
            'stats'    => [
                'published' => HomepageSection::where('is_published', true)->count(),
                'draft'     => HomepageSection::where('is_published', false)->count(),
                'total'     => HomepageSection::count(),
            ],
        ]);
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

    // --------------------------------------------------------------------------
    // Bulk, duplicate & ordering
    // --------------------------------------------------------------------------
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:100'],
            'ids.*'  => ['integer', 'exists:homepage_sections,id'],
            'action' => ['required', 'in:publish,unpublish,duplicate,delete'],
        ]);

        $sections = HomepageSection::whereIn('id', $validated['ids'])->get();

        $newKeyBase = 'copy-'.now()->format('ymd-His');
        $changed = 0;

        foreach ($sections as $section) {
            match ($validated['action']) {
                'publish' => (function () use ($section, &$changed) {
                    $section->update(['is_published' => true]);
                    $changed++;
                })(),
                'unpublish' => (function () use ($section, &$changed) {
                    $section->update(['is_published' => false]);
                    $changed++;
                })(),
                'duplicate' => (function () use ($section, &$changed, $newKeyBase) {
                    $this->duplicateSection($section, $newKeyBase, $changed);
                })(),
                'delete' => (function () use ($section, &$changed) {
                    $this->audit->record('homepage.bulk.delete', $section);
                    $section->delete();
                    $changed++;
                })(),
            };
        }

        $this->audit->record('homepage.bulk.update', null, [], [
            'action' => $validated['action'],
            'ids'    => $validated['ids'],
        ]);

        return back()->with('success', $changed.' section(s) updated.');
    }

    public function duplicate(HomepageSection $homepage_section): RedirectResponse
    {
        $newKeyBase = 'copy-'.now()->format('ymd-His');
        $counter = 0;

        $this->duplicateSection($homepage_section, $newKeyBase, $counter);
        $this->audit->record('homepage.duplicate', $homepage_section, [], ['duplicated' => $counter]);

        return back()->with('success', 'Section duplicated as a draft.');
    }

    public function move(HomepageSection $homepage_section, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $siblings = HomepageSection::orderBy('sort_order')->orderBy('id')->get();

        $index = $siblings->search(fn (HomepageSection $sibling) => $sibling->id === $homepage_section->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $target < 0 || $target >= $siblings->count()) {
            return back()->with('success', 'Section is already at the edge of its group.');
        }

        $ordered = $siblings->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        DB::transaction(function () use ($ordered) {
            foreach ($ordered as $position => $sibling) {
                if ((int) $sibling->sort_order !== $position) {
                    $sibling->update(['sort_order' => $position]);
                }
            }
        });

        $this->audit->record('homepage.reorder', $homepage_section, null, ['direction' => $direction]);

        return back()->with('success', 'Homepage order updated.');
    }

    /** Copy a section as an unpublished draft with a unique key. */
    private function duplicateSection(HomepageSection $section, string $keyBase, int &$counter): void
    {
        $copy = $section->replicate();
        // A fresh identity for the copy — replicate() would otherwise reuse the uuid.
        $copy->uuid = null;
        $copy->key = $this->uniqueKey($keyBase);
        $copy->title = ($section->title ? $section->title.' ' : '').'(copy)';
        $copy->is_published = false;
        $copy->sort_order = $section->sort_order + 1;
        $copy->save();

        $counter++;
    }

    private function uniqueKey(string $base): string
    {
        $key = $base;
        $attempt = 1;

        while (HomepageSection::where('key', $key)->exists()) {
            $key = $base.'-'.++$attempt;
        }

        return $key;
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
