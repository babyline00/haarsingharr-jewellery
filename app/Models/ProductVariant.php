<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'product_id', 'sku', 'barcode', 'title', 'attributes',
        'regular_price', 'sale_price', 'cost_price',
        'sale_starts_at', 'sale_ends_at',
        'stock_quantity', 'reserved_stock', 'safety_stock',
        'low_stock_threshold', 'allow_backorder', 'is_default',
        'status', 'weight_grams', 'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'attributes'         => 'array',
            'regular_price'      => 'decimal:2',
            'sale_price'         => 'decimal:2',
            'cost_price'         => 'decimal:2',
            'weight_grams'       => 'decimal:2',
            'sale_starts_at'     => 'datetime',
            'sale_ends_at'       => 'datetime',
            'stock_quantity'     => 'integer',
            'reserved_stock'     => 'integer',
            'safety_stock'       => 'integer',
            'low_stock_threshold'=> 'integer',
            'allow_backorder'    => 'boolean',
            'is_default'         => 'boolean',
        ];
    }

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    public function stockReservations(): MorphMany
    {
        return $this->morphMany(StockReservation::class, 'reservable');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeActive(Builder $q)
    {
        return $q->where('status', 'active');
    }

    public function scopeInStock(Builder $q)
    {
        return $q->whereColumn('stock_quantity', '>', 'reserved_stock');
    }

    public function scopeLowStock(Builder $q)
    {
        return $q->whereColumn('stock_quantity', '<=', 'reserved_stock')
            ->whereNotNull('low_stock_threshold')
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    public function availableStock(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_stock - $this->safety_stock);
    }

    public function isOnSale(): bool
    {
        if ($this->sale_price === null || $this->sale_price >= $this->regular_price) {
            return false;
        }
        if ($this->sale_starts_at && $this->sale_starts_at->isFuture()) {
            return false;
        }
        if ($this->sale_ends_at && $this->sale_ends_at->isPast()) {
            return false;
        }
        return true;
    }

    public function currentPrice(): float
    {
        if ($this->isOnSale()) {
            return (float) $this->sale_price;
        }
        return (float) $this->regular_price ?: 0;
    }

    public function hasStock(int $quantity): bool
    {
        return $this->availableStock() >= $quantity
            || ($this->allow_backorder && config('storefront.inventory.allow_backorders'));
    }

    public function formattedAttributes(): string
    {
        if (! $this->attributes) {
            return '';
        }
        return collect($this->attributes)
            ->map(fn ($value, $key) => ucfirst($key).': '.$value)
            ->implode(' • ');
    }
}
