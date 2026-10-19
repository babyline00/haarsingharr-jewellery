<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request): View
    {
        $categories = Category::with('parent')->search($request->input('q'))->ordered()->paginate(20);
        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $categories = Category::ordered()->get();
        return view('admin.categories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:140|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:300',
            'banner_image' => 'nullable|string|max:300',
            'status' => 'required|in:active,inactive',
            'show_in_menu' => 'sometimes|boolean',
            'show_on_homepage' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'seo_title' => 'nullable|string|max:200',
            'seo_description' => 'nullable|string|max:500',
        ]);

        $category = new Category($validated);
        if (! empty($validated['parent_id']) && ! $category->canHaveParent((int) $validated['parent_id'])) {
            return back()->with('error', 'Circular category relationship detected.')->withInput();
        }
        $category->save();
        $this->audit->record('categories.create', $category);
        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function show(Category $category) { return redirect()->route('admin.categories.edit', $category); }

    public function edit(Category $category): View
    {
        $categories = Category::where('id', '!=', $category->id)->ordered()->get();
        return view('admin.categories.edit', compact('category', 'categories'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:300',
            'banner_image' => 'nullable|string|max:300',
            'status' => 'required|in:active,inactive',
            'show_in_menu' => 'sometimes|boolean',
            'show_on_homepage' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'seo_title' => 'nullable|string|max:200',
            'seo_description' => 'nullable|string|max:500',
        ]);

        if (! empty($validated['parent_id']) && ! $category->canHaveParent((int) $validated['parent_id'])) {
            return back()->with('error', 'Circular category relationship detected.')->withInput();
        }
        $category->update($validated);
        $this->audit->record('categories.update', $category);
        return back()->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $this->audit->record('categories.delete', $category);
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
