<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory, HasSlug, HasUuid, LogsActivity, SoftDeletes {
        HasUuid::getRouteKey insteadof HasSlug;
    }

    protected $fillable = [
        'category_id', 'subcategory_id', 'name', 'slug', 'sku', 'barcode',
        'short_description', 'description',
        'regular_price', 'sale_price', 'cost_price',
        'sale_starts_at', 'sale_ends_at',
        'status', 'is_featured', 'is_best_seller', 'is_new_arrival',
        'is_bridal', 'is_men', 'allow_reviews', 'allow_backorders',
        'weight_grams', 'metal', 'gemstone', 'carat', 'size', 'colour',
        'dimensions', 'material', 'care_instructions',
        'has_variants', 'variant_attributes',
        'seo_title', 'seo_description', 'seo_image', 'canonical_url',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status'               => ProductStatus::class,
            'regular_price'        => 'decimal:2',
            'sale_price'           => 'decimal:2',
            'cost_price'           => 'decimal:2',
            'weight_grams'         => 'decimal:2',
            'carat'                => 'decimal:3',
            'sale_starts_at'        => 'datetime',
            'sale_ends_at'         => 'datetime',
            'is_featured'          => 'boolean',
            'is_best_seller'       => 'boolean',
            'is_new_arrival'       => 'boolean',
            'is_bridal'            => 'boolean',
            'is_men'               => 'boolean',
            'allow_reviews'        => 'boolean',
            'allow_backorders'     => 'boolean',
            'has_variants'         => 'boolean',
            'variant_attributes'   => 'array',
            'view_count'           => 'integer',
            'units_sold'           => 'integer',
            'rating_average'       => 'decimal:2',
            'rating_count'         => 'integer',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function primaryImage(): BelongsTo
    {
        // Use a relationship to the first image; useful for caching.
        return $this->belongsTo(ProductImage::class, 'id', 'product_id')
            ->where('is_primary', true)
            ->withDefault(fn ($image, $product) => $image->fill(['path' => null]));
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideo::class)->orderByDesc('is_primary')->orderBy('sort_order');
    }

    public function defaultVariant(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->where('is_default', true)->orWhere('is_default', false)->oldest();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_tag', 'product_id', 'product_tag_id');
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class);
    }

    public function relatedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations',
            'product_id', 'related_product_id')
            ->wherePivot('relation_type', 'related')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function upsells(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations',
            'product_id', 'related_product_id')
            ->wherePivot('relation_type', 'upsell');
    }

    public function crossSells(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_relations',
            'product_id', 'related_product_id')
            ->wherePivot('relation_type', 'cross_sell');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true)->latest();
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    public function inventoryMovements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeActive(Builder $q)
    {
        return $q->where('status', ProductStatus::Active);
    }

    public function scopeDraft(Builder $q)
    {
        return $q->where('status', ProductStatus::Draft);
    }

    public function scopeArchived(Builder $q)
    {
        return $q->where('status', ProductStatus::Archived);
    }

    public function scopeFeatured(Builder $q)
    {
        return $q->where('is_featured', true)->active();
    }

    public function scopeBestSellers(Builder $q)
    {
        return $q->where('is_best_seller', true)->active();
    }

    public function scopeNewArrivals(Builder $q)
    {
        return $q->where('is_new_arrival', true)->active();
    }

    public function scopeBridal(Builder $q)
    {
        return $q->where('is_bridal', true)->active();
    }

    public function scopeMen(Builder $q)
    {
        return $q->where('is_men', true)->active();
    }

    public function scopeSearch(Builder $q, ?string $term)
    {
        if (! $term) {
            return $q;
        }
        return $q->where(function (Builder $sub) use ($term) {
            $sub->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeInBudget(Builder $q, float $min, float $max)
    {
        return $q->whereBetween('sale_price', [$min, $max])
            ->orWhereBetween('regular_price', [$min, $max]);
    }

    public function scopePopular(Builder $q, int $limit = 20)
    {
        return $q->orderByDesc('view_count')->limit($limit);
    }

    public function scopeTrending(Builder $q, int $days = 30)
    {
        return $q->withCount(['orderItems' => function ($q) use ($days) {
            $q->where('order_items.created_at', '>=', now()->subDays($days));
        }])->orderByDesc('order_items_count');
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    public function currentPrice(): float
    {
        $variant = $this->variants()->where('is_default', true)->first();
        if ($variant) {
            return $variant->currentPrice();
        }
        if ($this->isOnSale()) {
            return (float) $this->sale_price;
        }
        return (float) $this->regular_price ?: 0;
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

    public function discountPercentage(): ?float
    {
        if (! $this->isOnSale() || ! $this->regular_price) {
            return null;
        }
        return round((1 - ($this->sale_price / $this->regular_price)) * 100, 1);
    }

    public function availableStock(): int
    {
        return $this->variants->sum(fn ($v) => $v->availableStock());
    }

    public function inStock(): bool
    {
        return $this->availableStock() > 0 || $this->allow_backorders;
    }

    public function primaryImagePath(): ?string
    {
        $primary = $this->images->firstWhere('is_primary', true);
        return $primary?->path ?? $this->images->first()?->path;
    }

    public function primaryImageCardPath(): ?string
    {
        $primary = $this->images->firstWhere('is_primary', true);
        return $primary?->card_path
            ?? $primary?->path
            ?? $this->images->first()?->card_path
            ?? $this->images->first()?->path;
    }

    public function recordView(): void
    {
        $this->increment('view_count');
    }

    public function updateRating(): void
    {
        $stats = $this->reviews()
            ->where('is_approved', true)
            ->selectRaw('COALESCE(AVG(rating), 0) as avg, COUNT(*) as count')
            ->first();

        $this->update([
            'rating_average' => round($stats->avg, 2),
            'rating_count'  => $stats->count,
        ]);
    }
}
