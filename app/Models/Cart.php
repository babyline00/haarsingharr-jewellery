<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id', 'session_id', 'currency',
        'subtotal', 'discount', 'shipping', 'tax', 'total',
        'coupon_id', 'coupon_code', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'   => 'decimal:2',
            'discount'   => 'decimal:2',
            'shipping'   => 'decimal:2',
            'tax'        => 'decimal:2',
            'total'      => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeForUser(Builder $q, User $user)
    {
        return $q->where('user_id', $user->id);
    }

    public function scopeForSession(Builder $q, string $sessionId)
    {
        return $q->whereNull('user_id')->where('session_id', $sessionId);
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    public function itemCount(): int
    {
        return $this->items->sum('quantity');
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function recalculate(): self
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $variant = $item->productVariant;
            $unitPrice = $variant?->currentPrice()
                ?? (float) $item->product->currentPrice();
            $lineTotal = $unitPrice * $item->quantity;
            $item->update([
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
            $subtotal += $lineTotal;
        }
        $this->subtotal = $subtotal;
        $this->total = $subtotal - $this->discount + $this->shipping + $this->tax;
        $this->save();
        return $this;
    }

    public function addVariant(ProductVariant $variant, int $quantity, array $metadata = []): CartItem
    {
        if (! $variant->hasStock($quantity)) {
            throw new \App\Exceptions\InsufficientStockException(
                "Only {$variant->availableStock()} units available for SKU {$variant->sku}"
            );
        }

        $existing = $this->items()
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            if (! $variant->hasStock($newQty)) {
                throw new \App\Exceptions\InsufficientStockException(
                    "Only {$variant->availableStock()} units available."
                );
            }
            $existing->update([
                'quantity'  => $newQty,
                'metadata'  => array_merge($existing->metadata ?? [], $metadata),
            ]);
            return $existing;
        }

        $unitPrice = $variant->currentPrice();
        return $this->items()->create([
            'product_id'         => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity'           => $quantity,
            'unit_price'         => $unitPrice,
            'line_total'         => $unitPrice * $quantity,
            'metadata'           => $metadata,
        ]);
    }
}
