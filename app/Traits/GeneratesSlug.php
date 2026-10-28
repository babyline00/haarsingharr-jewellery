<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Fills a model's unique `slug` column from a human-readable source on create.
 *
 * Some models expose a slug in the storefront but are not managed by
 * spatie/laravel-sluggable (which also rewrites route keys). This keeps those
 * models binding by primary key while still guaranteeing a slug.
 */
trait GeneratesSlug
{
    protected static function bootGeneratesSlug(): void
    {
        static::creating(function ($model) {
            if (blank($model->slug)) {
                $model->slug = $model->uniqueSlug($model->slugSource());
            }
        });
    }

    protected function slugSource(): string
    {
        return (string) ($this->name ?? $this->title ?? $this->code ?? 'item');
    }

    protected function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'item';
        $slug = $base;
        $i = 1;

        while ($this->slugExists($slug)) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::query();

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            $query = $query->withTrashed();
        }

        return $query->where('slug', $slug)->exists();
    }
}
