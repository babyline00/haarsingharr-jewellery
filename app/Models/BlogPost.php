<?php

namespace App\Models;

use App\Traits\GeneratesSlug;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use HasFactory, GeneratesSlug, HasUuid, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'status', 'published_at', 'seo_title', 'seo_description',
        'author_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected function slugSource(): string
    {
        return (string) ($this->title ?? 'post');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag');
    }

    /** Post is visible on the storefront: published and past its release time. */
    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published')
            ->where(fn (Builder $q) => $q
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now()));
    }

    public function scopeScheduled(Builder $q): Builder
    {
        return $q->where(function (Builder $q) {
            $q->where('status', 'scheduled')
                ->orWhere(fn (Builder $q) => $q
                    ->where('status', 'published')
                    ->where('published_at', '>', now()));
        });
    }
}
