<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\GiftClaim;
use App\Models\GiftPopupEvent;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Issues single-use gift coupons for the homepage popup and admin giveaways.
 *
 * Prize configuration is intentionally human-editable. Each line in the
 * `gift_popup_prizes` setting can be:
 *
 *   Label
 *   Label|value
 *   Label|type|value
 *   Label|type|value|weight
 *
 * `type` is one of percentage, fixed or free_shipping. When omitted the
 * global gift coupon type is used. Weights are relative.
 */
class GiftCouponService
{
    public function settings(): array
    {
        return [
            'enabled'       => $this->boolSetting('gift_popup_enabled', false),
            'mode'          => $this->mode(),
            'unique'        => $this->boolSetting('gift_unique_coupons', true),
            'prefix'        => $this->prefix(),
            'type'          => $this->couponType(),
            'value'         => (float) StoreSetting::get('gift_coupon_value', 10),
            'min_order'     => (float) StoreSetting::get('gift_coupon_min_order', 0),
            'expiry_days'   => max(1, (int) StoreSetting::get('gift_coupon_expiry_days', 30)),
            'static_coupon' => (string) StoreSetting::get('gift_popup_coupon', ''),
            'fallback_prize'=> (string) StoreSetting::get('gift_popup_prize', '10% OFF your next order'),
        ];
    }

    /**
     * @return array<int, array{label:string,type:string,value:float,weight:float}>
     */
    public function prizes(): array
    {
        $settings = $this->settings();
        $raw = (string) StoreSetting::get('gift_popup_prizes', '');

        $prizes = [];

        foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $label = $parts[0] !== '' ? $parts[0] : 'A little gift';
            $type = $settings['type'];
            $value = $settings['value'];
            $weight = 1.0;

            if (count($parts) === 2) {
                $value = (float) $parts[1];
            } elseif (count($parts) >= 3) {
                $type = $parts[1] !== '' ? $parts[1] : $settings['type'];
                $value = (float) $parts[2];
                if (isset($parts[3]) && $parts[3] !== '') {
                    $weight = (float) $parts[3];
                }
            }

            $prizes[] = [
                'label'  => $label,
                'type'   => $this->normaliseType($type, $settings['type']),
                'value'  => max(0, $value),
                'weight' => max(0.01, $weight),
            ];
        }

        if ($prizes === []) {
            $prizes[] = [
                'label'  => $settings['fallback_prize'],
                'type'   => $settings['type'],
                'value'  => $settings['value'],
                'weight' => 1.0,
            ];
        }

        return $prizes;
    }

    /**
     * Pick a prize using the configured relative weights.
     *
     * @return array{label:string,type:string,value:float,weight:float}
     */
    public function pickPrize(): array
    {
        $prizes = $this->prizes();
        $total = array_sum(array_column($prizes, 'weight'));
        $roll = (mt_rand() / mt_getrandmax()) * $total;

        foreach ($prizes as $prize) {
            $roll -= $prize['weight'];
            if ($roll <= 0) {
                return $prize;
            }
        }

        return $prizes[array_key_last($prizes)];
    }

    /**
     * Claim the popup gift for the current session/user. A session can only
     * claim once per 24 hours; returning the original claim makes the endpoint
     * idempotent after a refresh.
     *
     * @return array{claim:GiftClaim,already_claimed:bool,code:?string}
     */
    public function claim(array $context): array
    {
        $sessionId = $context['session_id'] ?? null;
        $userId = $context['user_id'] ?? null;

        if ($sessionId || $userId) {
            $existing = GiftClaim::query()
                ->whereIn('mode', ['scratch', 'spin', 'both'])
                ->where('claimed_at', '>=', now()->subDay())
                ->where(function ($query) use ($sessionId, $userId) {
                    if ($sessionId) {
                        $query->where('session_id', $sessionId);
                    }
                    if ($userId) {
                        $query->orWhere('user_id', $userId);
                    }
                })
                ->latest('claimed_at')
                ->first();

            if ($existing) {
                return [
                    'claim'           => $existing,
                    'already_claimed' => true,
                    'code'            => $existing->coupon?->code ?? $this->settings()['static_coupon'],
                ];
            }
        }

        $settings = $this->settings();
        $prize = $this->pickPrize();

        $coupon = $settings['unique']
            ? $this->issueCoupon($prize, $settings['expiry_days'], $settings['min_order'], $settings['prefix'])
            : null;

        $claim = $this->createClaim([
            'user_id'       => $userId,
            'coupon_id'     => $coupon?->id,
            'session_id'    => $sessionId,
            'customer_name' => $context['customer_name'] ?? null,
            'email'         => $context['email'] ?? null,
            'prize'         => $prize['label'],
            'mode'          => $context['mode'] ?? $settings['mode'],
            'ip_address'    => $context['ip_address'] ?? null,
            'user_agent'    => $context['user_agent'] ?? null,
            'claimed_at'    => now(),
        ], $coupon, true);

        return [
            'claim'           => $claim,
            'already_claimed' => false,
            'code'            => $coupon?->code ?? $settings['static_coupon'],
        ];
    }

    /**
     * Issue a single-use coupon for a prize. Used by both popup claims and
     * giveaway draws.
     */
    public function issueCoupon(array $prize, ?int $expiryDays = null, ?float $minOrder = null, ?string $prefix = null): Coupon
    {
        $settings = $this->settings();
        $type = $this->normaliseType($prize['type'] ?? $settings['type'], $settings['type']);
        $value = $type === 'free_shipping' ? 0 : max(0, (float) ($prize['value'] ?? $settings['value']));
        $expiryDays = max(1, $expiryDays ?? $settings['expiry_days']);
        $minOrder = $minOrder ?? $settings['min_order'];
        $prefix = $this->sanitisePrefix($prefix ?? $settings['prefix']);

        return Coupon::create([
            'code'                     => $this->uniqueCode($prefix),
            'type'                     => $type,
            'value'                    => $value,
            'min_order_value'          => $minOrder > 0 ? $minOrder : null,
            'usage_limit_total'        => 1,
            'usage_limit_per_customer' => 1,
            'used_count'               => 0,
            'first_order_only'         => false,
            'exclude_sale_items'       => false,
            'starts_at'                => now(),
            'expires_at'               => now()->addDays($expiryDays),
            'status'                   => 'active',
        ]);
    }

    public function createClaim(array $attributes, ?Coupon $coupon = null, bool $trackEvent = false): GiftClaim
    {
        return DB::transaction(function () use ($attributes, $coupon, $trackEvent) {
            $claim = GiftClaim::create(array_merge([
                'coupon_id'  => $coupon?->id,
                'status'     => 'issued',
                'claimed_at' => now(),
            ], $attributes));

            if ($trackEvent) {
                GiftPopupEvent::create([
                    'event'         => 'claim',
                    'session_id'    => $claim->session_id,
                    'user_id'       => $claim->user_id,
                    'gift_claim_id' => $claim->id,
                    'mode'          => $claim->mode,
                    'ip_address'    => $claim->ip_address,
                ]);
            }

            return $claim;
        });
    }

    public function uniqueCode(?string $prefix = null): string
    {
        $prefix = $this->sanitisePrefix($prefix ?? $this->prefix());

        do {
            $code = $prefix.'-'.strtoupper(Str::random(6));
        } while (Coupon::withTrashed()->where('code', $code)->exists());

        return $code;
    }

    public function mode(): string
    {
        $mode = (string) StoreSetting::get('gift_popup_mode', 'scratch');

        return in_array($mode, ['scratch', 'spin', 'both'], true) ? $mode : 'scratch';
    }

    public function couponType(): string
    {
        return $this->normaliseType((string) StoreSetting::get('gift_coupon_type', 'percentage'), 'percentage');
    }

    public function prefix(): string
    {
        return $this->sanitisePrefix((string) StoreSetting::get('gift_coupon_prefix', 'GIFT'));
    }

    private function normaliseType(string $type, string $fallback = 'percentage'): string
    {
        return in_array($type, ['percentage', 'fixed', 'free_shipping'], true) ? $type : $fallback;
    }

    private function sanitisePrefix(string $prefix): string
    {
        $prefix = preg_replace('/[^A-Z0-9]/', '', strtoupper($prefix)) ?: 'GIFT';

        return substr($prefix, 0, 12);
    }

    private function boolSetting(string $key, bool $default): bool
    {
        return filter_var(StoreSetting::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
