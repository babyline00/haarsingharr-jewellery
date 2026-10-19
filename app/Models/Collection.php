<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Collection extends Model
{
    use HasFactory, HasSlug, HasUuid, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'banner_image',
        'type', 'rules', 'match_condition',
        'status', 'show_on_homepage', 'sort_order',
        'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'rules'           => 'array',
            'show_on_homepage'=> 'boolean',
            'sort_order'      => 'integer',
            'starts_at'       => 'datetime',
            'ends_at'         => 'datetime',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->preventOverwrite();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopeActive(Builder $q)
    {
        return $q->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeShownOnHomepage(Builder $q)
    {
        return $q->active()->where('show_on_homepage', true)->orderBy('sort_order');
    }

    public function scopeManual(Builder $q)
    {
        return $q->where('type', 'manual');
    }

    public function scopeRuleBased(Builder $q)
    {
        return $q->where('type', 'rule_based');
    }
}
