<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'code', 'slug', 'type', 'value',
        'min_order_value', 'max_discount_amount',
        'product_id', 'category_id',
        'usage_limit_total', 'usage_limit_per_customer',
        'used_count', 'first_order_only', 'exclude_sale_items',
        'starts_at', 'expires_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'value'                    => 'decimal:2',
            'min_order_value'          => 'decimal:2',
            'max_discount_amount'      => 'decimal:2',
            'usage_limit_total'        => 'integer',
            'usage_limit_per_customer' => 'integer',
            'used_count'                => 'integer',
            'first_order_only'         => 'boolean',
            'exclude_sale_items'       => 'boolean',
            'starts_at'                 => 'datetime',
            'expires_at'                => 'datetime',
        ];
    }

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->usage_limit_total && $this->used_count >= $this->usage_limit_total) {
            return false;
        }
        return true;
    }

    public function scopeActive(Builder $q)
    {
        return $q->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    public function scopeForCode(Builder $q, string $code)
    {
        return $q->whereRaw('LOWER(code) = ?', [strtolower($code)]);
    }

    public function scopeExpired(Builder $q)
    {
        return $q->where('expires_at', '<', now());
    }
}
