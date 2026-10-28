<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function show(Collection $collection): View
    {
        abort_if($collection->status !== 'active', 404);
        $products = $collection->products()->active()->with(['images', 'variants'])->withCount('wishlistItems')->paginate(24);
        return view('storefront.collection.show', [
            'collection' => $collection,
            'products'   => $products,
        ]);
    }
}
