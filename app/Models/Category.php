<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Category extends Model
{
    use HasFactory, HasSlug, HasUuid, SoftDeletes {
        HasUuid::getRouteKey insteadof HasSlug;
    }

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'short_description',
        'banner_image', 'icon', 'status', 'sort_order', 'depth',
        'left', 'right', 'path',
        'seo_title', 'seo_description', 'seo_image',
        'show_in_menu', 'show_on_homepage',
    ];

    protected function casts(): array
    {
        return [
            'show_in_menu'       => 'boolean',
            'show_on_homepage'   => 'boolean',
            'sort_order'         => 'integer',
            'depth'              => 'integer',
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
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function subcategoryProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'subcategory_id');
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeShownInMenu($query)
    {
        return $query->where('show_in_menu', true)->active()->ordered();
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($sub) use ($term) {
            $sub->where('name', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    // --------------------------------------------------------------------------
    // Cache helpers
    // --------------------------------------------------------------------------
    /**
     * Active categories, memoised for a short while. Returns a Collection so
     * callers can further filter in-memory (e.g. the navigation mega menu).
     */
    public static function cached(): Collection
    {
        return cache()->remember('storefront.categories', now()->addHour(), function () {
            return static::query()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });
    }

    // --------------------------------------------------------------------------
    // Helpers
    // --------------------------------------------------------------------------
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Prevent circular category relationships at the model layer in addition to
     * the form-request layer.
     */
    public function canHaveParent(?int $parentId): bool
    {
        if (! $parentId) {
            return true;
        }
        if ((int) $parentId === $this->id) {
            return false;
        }
        $ancestor = Category::find($parentId);
        while ($ancestor) {
            if ($ancestor->id === $this->id) {
                return false;
            }
            $ancestor = $ancestor->parent;
        }
        return true;
    }

    public function breadcrumb(): array
    {
        $chain = collect();
        $node  = $this;
        while ($node) {
            $chain->prepend($node);
            $node = $node->parent;
        }
        return $chain->all();
    }
}
