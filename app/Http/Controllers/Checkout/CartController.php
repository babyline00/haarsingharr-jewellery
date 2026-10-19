<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\AddToCartRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Exceptions\InsufficientStockException;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function store(AddToCartRequest $request): RedirectResponse
    {
        try {
            $this->cartService->addVariant(
                $request->getVariant(),
                $request->integer('quantity', 1),
                $request->input('metadata', []),
            );
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Added to cart.');
    }

    public function update(UpdateCartItemRequest $request, int $cartItemId): RedirectResponse
    {
        try {
            $this->cartService->updateQuantity($cartItemId, $request->integer('quantity'));
        } catch (InsufficientStockException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Cart updated.');
    }

    public function destroy(int $cartItemId): RedirectResponse
    {
        $this->cartService->remove($cartItemId);
        return back()->with('success', 'Item removed from cart.');
    }

    public function applyCoupon(ApplyCouponRequest $request): RedirectResponse
    {
        try {
            $this->cartService->applyCoupon($request->string('code')->trim()->toString());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('success', 'Coupon applied.');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cartService->removeCoupon();
        return back()->with('success', 'Coupon removed.');
    }
}
