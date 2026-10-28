<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuditService;
use App\Support\ImageCompressor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $term = $request->input('q');

        $categoryCollection = Category::with('parent')
            ->withCount(['products', 'subcategoryProducts'])
            ->search($term)
            ->ordered()
            ->get();

        $rows = $this->flattenTree($categoryCollection, filled($term));
        $perPage = 20;
        $page = max(1, LengthAwarePaginator::resolveCurrentPage());
        $categories = new LengthAwarePaginator(
            $categoryCollection->forPage($page, $perPage)->values(),
            $categoryCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $stats = [
            'total' => Category::count(),
            'active' => Category::where('status', 'active')->count(),
            'roots' => Category::whereNull('parent_id')->count(),
            'products' => Product::whereNotNull('category_id')->count(),
        ];

        return view('admin.categories.index', [
            // Legacy category templates use a paginated $categories variable;
            // the current tree view renders the complete hierarchy from rows.
            'categories' => $categories,
            'rows' => $rows,
            'filters' => ['q' => $term],
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        $categories = Category::ordered()->get();

        return view('admin.categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // Check for PHP upload errors directly from $_FILES (hasFile() returns false on upload errors)
        foreach (['banner_image_file', 'icon_file'] as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $errorCode = $_FILES[$field]['error'];
                if ($errorCode !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit ('.ini_get('upload_max_filesize').'). Try a smaller file or contact your host.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                    ];

                    return back()->withErrors([$field => $errorMessages[$errorCode] ?? 'Upload failed (error code: '.$errorCode.').'])->withInput();
                }
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:140|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:300',
            'banner_image' => 'nullable|string|max:300',
            'banner_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'icon' => 'nullable|string|max:300',
            'icon_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif,svg|max:5120',
            'status' => 'required|in:active,inactive',
            'show_in_menu' => 'sometimes|boolean',
            'show_on_homepage' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'seo_title' => 'nullable|string|max:200',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $validated['show_in_menu'] = $request->boolean('show_in_menu');
        $validated['show_on_homepage'] = $request->boolean('show_on_homepage');

        if ($request->hasFile('banner_image_file')) {
            $validated['banner_image'] = ImageCompressor::store($request->file('banner_image_file'), 'categories');
        }
        if ($request->hasFile('icon_file')) {
            $validated['icon'] = ImageCompressor::store($request->file('icon_file'), 'categories/icons');
        }
        unset($validated['banner_image_file'], $validated['icon_file']);

        $category = new Category($validated);
        if (! empty($validated['parent_id']) && ! $category->canHaveParent((int) $validated['parent_id'])) {
            return back()->with('error', 'Circular category relationship detected.')->withInput();
        }
        $category->save();
        $this->audit->record('categories.create', $category);

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function show(Category $category)
    {
        return redirect()->route('admin.categories.edit', $category);
    }

    public function edit(Category $category): View
    {
        $categories = Category::where('id', '!=', $category->id)->ordered()->get();

        return view('admin.categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category)
    {
        // Check for PHP upload errors directly from $_FILES (hasFile() returns false on upload errors)
        foreach (['banner_image_file', 'icon_file'] as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $errorCode = $_FILES[$field]['error'];
                if ($errorCode !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit ('.ini_get('upload_max_filesize').'). Try a smaller file or contact your host.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                    ];

                    return back()->withErrors([$field => $errorMessages[$errorCode] ?? 'Upload failed (error code: '.$errorCode.').'])->withInput();
                }
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:300',
            'banner_image' => 'nullable|string|max:300',
            'banner_image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
            'icon' => 'nullable|string|max:300',
            'icon_file' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif,svg|max:5120',
            'status' => 'required|in:active,inactive',
            'show_in_menu' => 'sometimes|boolean',
            'show_on_homepage' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'seo_title' => 'nullable|string|max:200',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $validated['show_in_menu'] = $request->boolean('show_in_menu');
        $validated['show_on_homepage'] = $request->boolean('show_on_homepage');

        if ($request->hasFile('banner_image_file')) {
            $validated['banner_image'] = ImageCompressor::store($request->file('banner_image_file'), 'categories');
        } elseif (empty($validated['banner_image']) && $category->banner_image) {
            $validated['banner_image'] = $category->banner_image;
        }
        if ($request->hasFile('icon_file')) {
            $validated['icon'] = ImageCompressor::store($request->file('icon_file'), 'categories/icons');
        } elseif (empty($validated['icon']) && $category->icon) {
            $validated['icon'] = $category->icon;
        }
        unset($validated['banner_image_file'], $validated['icon_file']);

        if (! empty($validated['parent_id']) && ! $category->canHaveParent((int) $validated['parent_id'])) {
            return back()->with('error', 'Circular category relationship detected.')->withInput();
        }
        $category->update($validated);
        $this->audit->record('categories.update', $category);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $blockers = $this->deleteBlockers($category);

        if ($blockers !== []) {
            return back()->with('error', $this->blockerMessage($category, $blockers));
        }

        $this->audit->record('categories.delete', $category);
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    public function move(Category $category, string $direction): RedirectResponse
    {
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $siblings = Category::query()
            ->when($category->parent_id, fn ($q) => $q->where('parent_id', $category->parent_id))
            ->when(! $category->parent_id, fn ($q) => $q->whereNull('parent_id'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $index = $siblings->search(fn (Category $sibling) => $sibling->id === $category->id);
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if ($index === false || $target < 0 || $target >= $siblings->count()) {
            return back()->with('success', 'Category is already at the edge of its group.');
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

        $this->audit->record('categories.reorder', $category, null, ['direction' => $direction]);

        return back()->with('success', 'Category order updated.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|exists:categories,id',
            'action' => 'required|in:activate,inactive,delete',
        ]);

        if ($data['action'] === 'delete' && ! $request->user()->can('categories.delete')) {
            abort(403);
        }

        if ($data['action'] !== 'delete' && ! $request->user()->can('categories.update')) {
            abort(403);
        }

        $categories = Category::whereIn('id', $data['ids'])->get();
        $changed = 0;
        $skipped = [];

        foreach ($categories as $category) {
            if ($data['action'] === 'delete') {
                if ($this->deleteBlockers($category) !== []) {
                    $skipped[] = $category->name;

                    continue;
                }

                $this->audit->record('categories.delete', $category);
                $category->delete();
                $changed++;

                continue;
            }

            $status = $data['action'] === 'activate' ? 'active' : 'inactive';
            $category->update(['status' => $status]);
            $this->audit->record('categories.bulk_status', $category, null, ['status' => $status]);
            $changed++;
        }

        $message = "{$changed} category(ies) updated.";
        if ($skipped !== []) {
            $message .= ' Skipped (has products or subcategories): '.implode(', ', array_slice($skipped, 0, 5));
            if (count($skipped) > 5) {
                $message .= '…';
            }
        }

        return back()->with($skipped === [] ? 'success' : 'error', $message);
    }

    /**
     * Flatten the category tree into display rows with a depth value.
     *
     * @param  Collection<int, Category>  $categories
     * @return array<int, array{category: Category, depth: int}>
     */
    private function flattenTree($categories, bool $flat): array
    {
        if ($flat) {
            return $categories->map(fn (Category $category) => [
                'category' => $category,
                'depth' => 0,
            ])->all();
        }

        $byParent = $categories->groupBy(fn (Category $category) => $category->parent_id ?? 0);
        $rows = [];
        $seen = [];

        $walk = function (int $parentId, int $depth) use (&$walk, &$rows, &$seen, $byParent) {
            if ($depth > 10) {
                return;
            }

            foreach ($byParent->get($parentId, collect()) as $category) {
                if (isset($seen[$category->id])) {
                    continue;
                }

                $seen[$category->id] = true;
                $rows[] = ['category' => $category, 'depth' => $depth];
                $walk((int) $category->id, $depth + 1);
            }
        };

        $walk(0, 0);

        // Include orphaned or cyclic records rather than hiding them.
        foreach ($categories as $category) {
            if (! isset($seen[$category->id])) {
                $seen[$category->id] = true;
                $rows[] = ['category' => $category, 'depth' => 0];
                $walk((int) $category->id, 1);
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function deleteBlockers(Category $category): array
    {
        $blockers = [];

        $children = $category->children()->count();
        if ($children > 0) {
            $blockers[] = "{$children} subcategory(ies)";
        }

        $products = $category->products()->count()
            + Product::where('subcategory_id', $category->id)->count();

        if ($products > 0) {
            $blockers[] = "{$products} product(s)";
        }

        return $blockers;
    }

    /**
     * @param  array<int, string>  $blockers
     */
    private function blockerMessage(Category $category, array $blockers): string
    {
        return "Cannot delete '{$category->name}' while it contains ".implode(' and ', $blockers).
            '. Reassign or remove them first.';
    }
}
