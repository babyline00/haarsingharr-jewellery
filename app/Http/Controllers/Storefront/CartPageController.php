<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\View\View;

class CartPageController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(): View
    {
        $cart = $this->cartService->current()->load(['items.productVariant.product.images']);
        return view('storefront.cart.index', [
            'cart' => $cart,
        ]);
    }
}
