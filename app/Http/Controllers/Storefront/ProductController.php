<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ProductSearchRequest;
use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::active()
            ->search($request->input('q'))
            ->when($request->input('category'), function ($q, $slug) {
                $cat = Category::where('slug', $slug)->first();
                if ($cat) $q->where('category_id', $cat->id);
            })
            ->when($request->input('min'), function ($q, $min) {
                // Grouped so the OR cannot escape the other query constraints.
                $q->where(function ($w) use ($min) {
                    $w->where('sale_price', '>=', $min)
                        ->orWhere('regular_price', '>=', $min);
                });
            })
            ->when($request->input('max'), function ($q, $max) {
                $q->where(function ($w) use ($max) {
                    $w->where('sale_price', '<=', $max)
                        ->orWhere('regular_price', '<=', $max);
                });
            })
            ->when($request->input('sort'), function ($q, $sort) {
                $q->when($sort === 'price_asc', fn ($q) => $q->orderByRaw('COALESCE(sale_price, regular_price) ASC'));
                $q->when($sort === 'price_desc', fn ($q) => $q->orderByRaw('COALESCE(sale_price, regular_price) DESC'));
                $q->when($sort === 'newest', fn ($q) => $q->latest());
                $q->when($sort === 'popular', fn ($q) => $q->orderByDesc('view_count'));
            }, fn ($q) => $q->latest())
            ->with(['images', 'variants'])->withCount('wishlistItems')
            ->paginate(24);

        $categories = Category::active()->root()->ordered()->get();
        $collections = Collection::active()->limit(8)->get();

        return view('storefront.shop.index', [
            'products'    => $products,
            'categories'  => $categories,
            'collections' => $collections,
            'filters'     => $request->only(['q', 'category', 'min', 'max', 'sort']),
            'query'       => (string) $request->input('q'),
        ]);
    }

    public function show(Product $product): View
    {
        abort_if($product->status !== ProductStatus::Active, 404);
<<<<<<< HEAD
        $product->load(['images', 'variants.images', 'tags', 'approvedReviews' => fn ($q) => $q->latest()->limit(10)]);
=======
        $product->load(['images', 'variants.images', 'videos', 'tags', 'approvedReviews' => fn ($q) => $q->latest()->limit(10)]);
>>>>>>> origin/main
        $product->recordView();

        // Track recently viewed products in session
        $recentlyViewed = session()->get('recently_viewed', []);
        $recentlyViewed = array_values(array_diff($recentlyViewed, [$product->id]));
        array_unshift($recentlyViewed, $product->id);
        $recentlyViewed = array_slice($recentlyViewed, 0, 8);
        session()->put('recently_viewed', $recentlyViewed);

        $recentlyViewedProducts = Product::active()
            ->whereIn('id', array_slice($recentlyViewed, 1, 4))
<<<<<<< HEAD
            ->with(['images', 'variants'])
=======
            ->with(['images', 'variants'])->withCount('wishlistItems')
>>>>>>> origin/main
            ->get();

        return view('storefront.product.show', [
            'product'       => $product,
            'relatedProducts' => $product->relatedProducts()->active()->limit(4)->get(),
            'crossSells'    => $product->crossSells()->active()->limit(3)->get(),
            'recentlyViewedProducts' => $recentlyViewedProducts,
        ]);
    }

    public function bestSellers(Request $request): View
    {
        $products = Product::active()->bestSellers()
            ->search($request->input('q'))
            ->with(['images', 'variants'])->withCount('wishlistItems')
            ->paginate(24);
        return view('storefront.shop.index', [
            'products' => $products,
            'categories' => Category::active()->root()->ordered()->get(),
            'collections' => Collection::active()->get(),
            'filters' => ['sort' => 'popular'],
            'pageTitle' => 'Best Sellers',
        ]);
    }

    public function newArrivals(Request $request): View
    {
        $products = Product::active()->newArrivals()
            ->search($request->input('q'))
            ->with(['images', 'variants'])->withCount('wishlistItems')
            ->paginate(24);
        return view('storefront.shop.index', [
            'products' => $products,
            'categories' => Category::active()->root()->ordered()->get(),
            'collections' => Collection::active()->get(),
            'filters' => ['sort' => 'newest'],
            'pageTitle' => 'New Arrivals',
        ]);
    }

    public function sale(Request $request): View
    {
        $products = Product::active()
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'regular_price')
            ->search($request->input('q'))
            ->with(['images', 'variants'])->withCount('wishlistItems')
            ->paginate(24);
        return view('storefront.shop.index', [
            'products' => $products,
            'categories' => Category::active()->root()->ordered()->get(),
            'collections' => Collection::active()->get(),
            'filters' => [],
            'pageTitle' => 'Sale',
        ]);
    }

    public function bridal(Request $request): View
    {
        $products = Product::active()->bridal()
            ->search($request->input('q'))
            ->with(['images', 'variants'])->withCount('wishlistItems')
            ->paginate(24);
        return view('storefront.shop.index', [
            'products' => $products,
            'categories' => Category::active()->root()->ordered()->get(),
            'collections' => Collection::active()->get(),
            'filters' => [],
            'pageTitle' => 'Bridal Jewellery',
        ]);
    }

    public function men(Request $request): View
    {
        $products = Product::active()->men()
            ->search($request->input('q'))
            ->with(['images', 'variants'])->withCount('wishlistItems')
            ->paginate(24);
        return view('storefront.shop.index', [
            'products' => $products,
            'categories' => Category::active()->root()->ordered()->get(),
            'collections' => Collection::active()->get(),
            'filters' => [],
            'pageTitle' => 'Jewellery for Men',
        ]);
    }

    public function budget(?string $range = null): View
    {
        $ranges = [
            'under-15k'       => [0, 15000],
            '15k-to-50k'      => [15000, 50000],
            '50k-to-150k'     => [50000, 150000],
            'above-150k'      => [150000, 9999999],
        ];

        [$min, $max] = $ranges[$range] ?? [0, 9999999];
        $products = Product::active()->inBudget($min, $max)
            ->with(['images', 'variants'])->withCount('wishlistItems')->paginate(24);

        return view('storefront.shop.index', [
            'products' => $products,
            'categories' => Category::active()->root()->ordered()->get(),
            'collections' => Collection::active()->get(),
            'filters' => ['min' => $min, 'max' => $max],
            'budgetRanges' => $ranges,
            'pageTitle' => 'Shop by Budget',
        ]);
    }
}
