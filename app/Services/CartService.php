<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CartService
{
    private const SESSION_KEY = 'cart_session_id';

    public function current(): Cart
    {
        $cart = $this->findOrCreate();

        if ($cart->expires_at && $cart->expires_at->isPast()) {
            $cart->delete();
            $cart = $this->findOrCreate();
        }

        return $cart;
    }

    private function findOrCreate(): Cart
    {
        if (auth()->check() && auth()->user()->isCustomer()) {
            $cart = Cart::forUser(auth()->user())->latest()->first();
            if (! $cart) {
                $cart = Cart::create([
                    'user_id'    => auth()->id(),
                    'session_id' => null,
                    'currency'   => config('app.currency', 'PKR'),
                    'expires_at' => now()->addMinutes(config('storefront.cart.expire_minutes', 43200)),
                ]);
            }
            return $cart;
        }

        $sessionId = $this->ensureSessionId();
        $cart = Cart::forSession($sessionId)->latest()->first();
        if (! $cart) {
            $cart = Cart::create([
                'user_id'    => null,
                'session_id' => $sessionId,
                'currency'   => config('app.currency', 'PKR'),
                'expires_at' => now()->addMinutes(config('storefront.cart.guest_expire_minutes', 10080)),
            ]);
        }
        return $cart;
    }

    /**
     * Migrate a guest cart onto the customer when they log in / register.
     */
    public function migrateGuestCartToUser(User $user): void
    {
        $sessionId = $this->ensureSessionId();
        $guestCart = Cart::forSession($sessionId)->latest()->first();
        if (! $guestCart || $guestCart->isEmpty()) {
            return;
        }

        $userCart = Cart::forUser($user)->latest()->first();
        if ($userCart) {
            // Merge items
            foreach ($guestCart->items as $guestItem) {
                $existing = $userCart->items()
                    ->where('product_variant_id', $guestItem->product_variant_id)
                    ->first();
                if ($existing) {
                    $existing->increment('quantity', $guestItem->quantity);
                } else {
                    $guestItem->update(['cart_id' => $userCart->id]);
                }
            }
            $guestCart->delete();
            $userCart->recalculate();
        } else {
            $guestCart->update([
                'user_id' => $user->id,
                'session_id' => null,
                'expires_at' => now()->addMinutes(config('storefront.cart.expire_minutes', 43200)),
            ]);
        }

        Session::forget(self::SESSION_KEY);
    }

    public function addVariant(ProductVariant $variant, int $quantity, array $metadata = []): Cart
    {
        $cart = $this->current();
        $cart->addVariant($variant, $quantity, $metadata);
        $cart->recalculate();
        return $cart;
    }

    public function addProduct(Product $product, int $quantity, array $metadata = []): Cart
    {
        $variant = $product->variants()->where('is_default', true)->first()
            ?? $product->variants()->active()->first()
            ?? $product->variants()->first();

        if (! $variant) {
            throw new \App\Exceptions\InsufficientStockException(
                'This product requires a variant selection.'
            );
        }
        return $this->addVariant($variant, $quantity, $metadata);
    }

    public function updateQuantity(int $cartItemId, int $quantity): Cart
    {
        $cart = $this->current();
        $item = $cart->items()->find($cartItemId);
        if (! $item) {
            throw new \DomainException('Cart item not found.');
        }
        $maxQty = config('storefront.cart.max_qty_per_item', 20);
        $quantity = max(1, min($quantity, $maxQty));

        $variant = $item->productVariant;
        if ($variant && ! $variant->hasStock($quantity)) {
            throw new \App\Exceptions\InsufficientStockException(
                "Only {$variant->availableStock()} units available."
            );
        }

        $item->update(['quantity' => $quantity]);
        $cart->recalculate();
        return $cart;
    }

    public function remove(int $cartItemId): Cart
    {
        $cart = $this->current();
        $cart->items()->where('id', $cartItemId)->delete();
        $cart->recalculate();
        return $cart;
    }

    public function applyCoupon(string $code): Cart
    {
        $cart = $this->current();
        $coupon = Coupon::active()->forCode($code)->first();

        if (! $coupon || ! $coupon->isValid()) {
            throw new \DomainException('This coupon is not valid or has expired.');
        }

        $user = auth()->user();
        if ($coupon->first_order_only && $user) {
            $hasOrdered = $user->orders()->whereNot('status', 'cancelled')->exists();
            if ($hasOrdered) {
                throw new \DomainException('This coupon is only valid for first-time orders.');
            }
        }

        if ($coupon->min_order_value && $cart->subtotal < $coupon->min_order_value) {
            throw new \DomainException(
                "Order must be at least Rs. {$coupon->min_order_value} to use this coupon."
            );
        }

        $perCustomer = $coupon->usage_limit_per_customer;
        if ($perCustomer && $user) {
            $used = CouponRedemption::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();
            if ($used >= $perCustomer) {
                throw new \DomainException('You have already used this coupon the maximum number of times.');
            }
        }

        $discount = app(CouponService::class)->compute($coupon, $cart);
        if ($discount <= 0) {
            throw new \DomainException('This coupon does not apply to your cart.');
        }

        $cart->update([
            'coupon_id'   => $coupon->id,
            'coupon_code' => $coupon->code,
            'discount'   => $discount,
        ]);
        $cart->recalculate();
        return $cart;
    }

    public function removeCoupon(): Cart
    {
        $cart = $this->current();
        $cart->update(['coupon_id' => null, 'coupon_code' => null, 'discount' => 0]);
        $cart->recalculate();
        return $cart;
    }

    private function ensureSessionId(): string
    {
        $sessionId = Session::get(self::SESSION_KEY);
        if (! $sessionId) {
            $sessionId = (string) Str::uuid();
            Session::put(self::SESSION_KEY, $sessionId);
        }
        return $sessionId;
    }
}
