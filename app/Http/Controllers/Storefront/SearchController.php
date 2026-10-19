<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim($request->input('q', ''));
        $products = Product::active()
            ->search($q)
            ->with(['images', 'variants'])
            ->paginate(24);

        return view('storefront.search.index', [
            'products' => $products,
            'query' => $q,
        ]);
    }
}
