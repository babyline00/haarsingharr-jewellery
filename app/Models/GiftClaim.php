<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiftClaim extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id', 'coupon_id', 'session_id', 'customer_name', 'email',
        'prize', 'mode', 'status', 'ip_address', 'user_agent',
        'claimed_at', 'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'claimed_at'  => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(GiftPopupEvent::class);
    }

    public function isRedeemed(): bool
    {
        return $this->status === 'redeemed';
    }

    public function couponCode(): ?string
    {
        return $this->coupon?->code;
    }

    public function discountLabel(): ?string
    {
        if (! $this->coupon) {
            return null;
        }

        return match ($this->coupon->type) {
            'percentage'   => rtrim(rtrim(number_format((float) $this->coupon->value, 2, '.', ''), '0'), '.').'% off',
            'fixed'        => 'Rs. '.number_format((float) $this->coupon->value).' off',
            'free_shipping'=> 'Free shipping',
            default        => null,
        };
    }
}
