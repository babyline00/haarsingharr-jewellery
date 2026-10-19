<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $wishlist = $this->getOrCreateWishlist($request);
        return view('account.wishlist.index', ['wishlist' => $wishlist->load('items.product.images')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);
        $wishlist = $this->getOrCreateWishlist($request);
        $wishlist->addProduct(Product::find($validated['product_id']),
            $validated['product_variant_id'] ?? null);
        return back()->with('success', 'Saved to wishlist.');
    }

    public function destroy(int $item): RedirectResponse
    {
        $wishlist = $this->getOrCreateWishlist(request());
        $wishlist->items()->where('id', $item)->delete();
        return back()->with('success', 'Removed from wishlist.');
    }

    private function getOrCreateWishlist(Request $request): Wishlist
    {
        if ($request->user()) {
            return Wishlist::firstOrCreate(['user_id' => $request->user()->id]);
        }
        return Wishlist::firstOrCreate(['session_id' => session()->getId()]);
    }
}
