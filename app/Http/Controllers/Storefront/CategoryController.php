<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function show(Category $category): View
    {
        abort_if($category->status !== 'active', 404);
        $category->load('children', 'parent');

        $products = $category->products()
            ->active()
            ->with(['images', 'variants'])
            ->paginate(24);

        $subcategories = $category->children()->active()->ordered()->get();

        return view('storefront.category.show', [
            'category' => $category,
            'products' => $products,
            'subcategories' => $subcategories,
            'breadcrumb' => $category->breadcrumb(),
        ]);
    }
}
