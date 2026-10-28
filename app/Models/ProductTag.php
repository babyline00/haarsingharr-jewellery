<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class ProductTag extends Model
{
    use HasFactory, HasSlug, HasUuid, SoftDeletes {
        HasUuid::getRouteKey insteadof HasSlug;
    }

    protected $fillable = ['name', 'slug', 'description'];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tag', 'product_tag_id', 'product_id');
    }
}
