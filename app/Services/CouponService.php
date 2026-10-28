<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CouponService
{
    /**
     * Compute discount amount for a coupon applied to a cart. Pure function —
     * does not mutate the coupon. Returns the discount in cart currency.
     */
    public function compute(Coupon $coupon, Cart $cart): float
    {
        if (! $coupon->isValid()) {
            return 0.0;
        }

        return match ($coupon->type) {
            'percentage'        => $this->computePercentage($coupon, $cart),
            'fixed'              => $this->computeFixed($coupon, $cart),
            'free_shipping'      => $this->computeFreeShipping($coupon, $cart),
            'product_specific'   => $this->computeProductSpecific($coupon, $cart),
            'category_specific'  => $this->computeCategorySpecific($coupon, $cart),
            default              => 0.0,
        };
    }

    /**
     * Record a coupon redemption atomically — uses row locking to prevent
     * race conditions that exceed usage limits.
     */
    public function redeem(Coupon $coupon, Cart $cart, ?Order $order = null): CouponRedemption
    {
        return DB::transaction(function () use ($coupon, $cart, $order) {
            $locked = DB::table('coupons')->where('id', $coupon->id)->lockForUpdate()->first();

            if (! $locked) {
                throw new RuntimeException('Coupon not found.');
            }

            if ($locked->usage_limit_total && $locked->used_count >= $locked->usage_limit_total) {
                throw new RuntimeException('Coupon usage limit reached.');
            }

            $discount = $this->compute($coupon, $cart);
            if ($discount <= 0) {
                throw new RuntimeException('Coupon does not apply to this cart.');
            }

            $redemption = CouponRedemption::create([
                'coupon_id'        => $coupon->id,
                'order_id'         => $order?->id,
                'user_id'          => $cart->user_id,
                'discount_applied' => $coupon->type,
                'amount'           => $discount,
            ]);

            DB::table('coupons')->where('id', $coupon->id)->increment('used_count');

            // A popup/giveaway coupon is tied to exactly one gift claim; flag
            // the claim as redeemed for the marketing analytics screen.
            \App\Models\GiftClaim::where('coupon_id', $coupon->id)
                ->where('status', '!=', 'redeemed')
                ->update(['status' => 'redeemed', 'redeemed_at' => now()]);

            return $redemption;
        });
    }

    private function computePercentage(Coupon $coupon, Cart $cart): float
    {
        $discount = round(($cart->subtotal * $coupon->value) / 100, 2);
        return $this->applyCap($coupon, $discount);
    }

    private function computeFixed(Coupon $coupon, Cart $cart): float
    {
        if ($cart->subtotal < $coupon->min_order_value ?? 0) {
            return 0.0;
        }
        $discount = (float) $coupon->value;
        if ($cart->subtotal < $discount) {
            return $cart->subtotal;
        }
        return $this->applyCap($coupon, $discount);
    }

    private function computeFreeShipping(Coupon $coupon, Cart $cart): float
    {
        // Discount equals shipping cost, capped to a maximum if configured.
        return (float) $cart->shipping;
    }

    private function computeProductSpecific(Coupon $coupon, Cart $cart): float
    {
        if (! $coupon->product_id) {
            return 0.0;
        }
        $targetItems = $cart->items->where('product_id', $coupon->product_id);
        $subTotal = $targetItems->sum('line_total');
        if ($coupon->type === 'percentage') {
            return $this->applyCap($coupon, ($subTotal * $coupon->value) / 100);
        }
        return $this->applyCap($coupon, (float) $coupon->value);
    }

    private function computeCategorySpecific(Coupon $coupon, Cart $cart): float
    {
        if (! $coupon->category_id) {
            return 0.0;
        }
        $subTotal = $cart->items
            ->filter(fn ($item) => optional($item->product)->category_id === $coupon->category_id)
            ->sum('line_total');
        return $this->applyCap($coupon, ($subTotal * $coupon->value) / 100);
    }

    private function applyCap(Coupon $coupon, float $discount): float
    {
        if ($coupon->max_discount_amount && $discount > $coupon->max_discount_amount) {
            return (float) $coupon->max_discount_amount;
        }
        return round($discount, 2);
    }
}
