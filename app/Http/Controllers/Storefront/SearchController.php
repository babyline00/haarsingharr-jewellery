<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim($request->input('q', ''));
        $products = Product::active()
            ->search($q)
            ->with(['images', 'variants'])->withCount('wishlistItems')
            ->paginate(24);

        return view('storefront.search.index', [
            'products' => $products,
            'query' => $q,
        ]);
    }

    /**
     * JSON endpoint for live search (returns top 5 results).
     */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $products = Product::active()
            ->search($q)
            ->with(['images'])
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->currentPrice(),
                    'formatted_price' => 'Rs. ' . number_format($product->currentPrice()),
                    'image' => \App\Support\Media::url($product->primaryImageCardPath()),
                    'url' => route('products.show', $product),
                    'category' => $product->category?->name,
                ];
            });

        return response()->json(['results' => $products]);
    }
}
