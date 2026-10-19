<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockReservation extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'product_variant_id', 'quantity',
        'reservable_type', 'reservable_id',
        'expires_at', 'released_at', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity'   => 'integer',
            'expires_at' => 'datetime',
            'released_at'=> 'datetime',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function reservable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $q)
    {
        return $q->whereNull('released_at')->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $q)
    {
        return $q->whereNull('released_at')->where('expires_at', '<=', now());
    }

    public function release(): self
    {
        if ($this->released_at) {
            return $this;
        }
        $this->update(['released_at' => now()]);
        $variant = $this->productVariant;
        $variant->decrement('reserved_stock', $this->quantity);
        return $this;
    }
}
