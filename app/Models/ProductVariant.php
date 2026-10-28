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

    public function scopeDefault(Builder $q)
    {
        return $q->where('is_default', true);
    }

    public function scopeInStock(Builder $q)
    {
        return $q->whereColumn('stock_quantity', '>', 'reserved_stock');
    }

    public function scopeLowStock(Builder $q)
    {
        return $q->whereNotNull('low_stock_threshold')
            ->whereRaw('(stock_quantity - reserved_stock) <= low_stock_threshold');
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    public function availableStock(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_stock - $this->safety_stock);
    }

    /**
     * Whether the variant can be sold right now. Backorders count when the
     * store-wide backorder setting allows them.
     */
    public function inStock(): bool
    {
        return $this->availableStock() > 0 || $this->backorderable();
    }

    public function backorderable(): bool
    {
        return (bool) $this->allow_backorder
            && (bool) config('storefront.inventory.allow_backorders', false);
    }

    public function stockStatus(): string
    {
        $available = $this->availableStock();

        if ($available <= 0) {
            return 'out_of_stock';
        }

        if ($this->low_stock_threshold !== null && $available <= $this->low_stock_threshold) {
            return 'low_stock';
        }

        return 'in_stock';
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

    public function savingsPercentage(): ?float
    {
        if (! $this->isOnSale() || ! $this->regular_price) {
            return null;
        }
        return round((1 - ($this->sale_price / $this->regular_price)) * 100, 1);
    }

    public function hasStock(int $quantity): bool
    {
        return $this->availableStock() >= $quantity || $this->backorderable();
    }

    public function attributeValue(string $name): ?string
    {
        $value = data_get($this->attributes, $name);

        return $value === null ? null : (string) $value;
    }

    public function displayLabel(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        $formatted = $this->formattedAttributes();

        return $formatted !== '' ? $formatted : 'Default';
    }

    public function primaryImagePath(): ?string
    {
        return $this->images->first()?->path;
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
