<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NavigationMenu;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NavigationMenuController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $menus = NavigationMenu::orderBy('location')->orderBy('name')->get();

        return view('admin.menus.index', [
            'menus'     => $menus,
            'locations' => NavigationMenu::LOCATIONS,
            'stats'     => [
                'total'   => $menus->count(),
                'main'    => $menus->where('location', 'main')->count(),
                'footer'  => $menus->where('location', 'footer')->count(),
                'mobile'  => $menus->where('location', 'mobile')->count(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.menus.create', [
            'locations'    => NavigationMenu::LOCATIONS,
            'linkOptions'  => $this->linkOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'slug'     => ['nullable', 'string', 'max:100', 'unique:navigation_menus,slug'],
            'location' => ['required', 'in:'.implode(',', NavigationMenu::LOCATIONS)],
            'items'    => 'nullable|string',
        ]);

        $slug = Str::slug($validated['slug'] ?? $validated['name']) ?: 'menu';
        $unique = $slug;
        while (NavigationMenu::where('slug', $unique)->exists()) {
            $unique = $slug.'-'.mt_rand(10, 99);
        }

        $menu = NavigationMenu::create([
            ...$validated,
            'slug'  => $unique,
            'items' => $this->decodeItems($request),
        ]);

        $this->audit->record('menus.create', $menu);

        return redirect()->route('admin.menus.edit', $menu)->with('success', 'Menu created.');
    }

    public function edit(NavigationMenu $menu, Request $request): View
    {
        return view('admin.menus.edit', [
            'menu'         => $menu,
            'locations'    => NavigationMenu::LOCATIONS,
            'linkOptions'  => $this->linkOptions(),
        ]);
    }

    public function update(Request $request, NavigationMenu $menu): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'location' => ['required', 'in:'.implode(',', NavigationMenu::LOCATIONS)],
            'items'    => 'nullable|string',
        ]);

        $menu->update([
            'name'     => $validated['name'],
            'location' => $validated['location'],
            'items'    => $this->decodeItems($request, $menu),
        ]);

        $this->audit->record('menus.update', $menu);

        return back()->with('success', 'Menu updated.');
    }

    public function destroy(NavigationMenu $menu): RedirectResponse
    {
        $this->audit->record('menus.delete', $menu);
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('success', 'Menu deleted. The storefront falls back to the built-in navigation.');
    }

    // -------------------------------------------------------------------------

    /**
     * The items JSON arriving from the builder (textarea + hidden JSON mirror).
     * Shapes each item into a stable record; children allowed one level deep.
     */
    private function decodeItems(Request $request, ?NavigationMenu $menu = null): ?array
    {
        $json = (string) $request->input('items', '');
        if (trim($json) === '') {
            return $menu->items ?? null;
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return $menu->items ?? null;
        }

        $normalise = fn (array $item) => [
            'label'      => (string) ($item['label'] ?? ''),
            'type'       => in_array($item['type'] ?? '', ['page', 'category', 'collection', 'custom'], true)
                ? $item['type'] : 'custom',
            'link'       => (string) ($item['link'] ?? ''),
            'is_visible' => (bool) ($item['is_visible'] ?? true),
            'children'   => [],
        ];

        return collect($decoded)
            ->map(function ($item) use ($normalise) {
                $clean = $normalise((array) $item);
                $clean['children'] = collect((array) ($item['children'] ?? []))
                    ->map(fn ($child) => collect($normalise((array) $child))->except('children')->all())
                    ->values()->all();

                return $clean;
            })
            ->values()
            ->all();
    }

    /** Picker feeds for the builder: pages, categories, collections. */
    private function linkOptions(): array
    {
        return [
            'pages'       => \App\Models\CmsPage::query()->orderBy('title')->get(['id', 'title', 'slug']),
            'categories'  => \App\Models\Category::query()->orderBy('name')->get(['id', 'name']),
            'collections' => \App\Models\Collection::query()->orderBy('name')->get(['id', 'name']),
        ];
    }
}
