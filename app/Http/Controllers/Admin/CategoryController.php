<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\AuditService;
use App\Support\ImageCompressor;
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
        // Check for PHP upload errors directly from $_FILES (hasFile() returns false on upload errors)
        foreach (['banner_image_file', 'icon_file'] as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE) {
                $errorCode = $_FILES[$field]['error'];
                if ($errorCode !== UPLOAD_ERR_OK) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (' . ini_get('upload_max_filesize') . '). Try a smaller file or contact your host.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                    ];
                    return back()->withErrors([$field => $errorMessages[$errorCode] ?? 'Upload failed (error code: ' . $errorCode . ').'])->withInput();
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

    public function show(Category $category) { return redirect()->route('admin.categories.edit', $category); }

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
                        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (' . ini_get('upload_max_filesize') . '). Try a smaller file or contact your host.',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please try again.',
                        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing. Contact your host.',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check permissions.',
                        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
                    ];
                    return back()->withErrors([$field => $errorMessages[$errorCode] ?? 'Upload failed (error code: ' . $errorCode . ').'])->withInput();
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

    public function destroy(Category $category)
    {
        $this->audit->record('categories.delete', $category);
        $category->delete();
        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }
}
