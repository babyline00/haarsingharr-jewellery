<?php

namespace App\Models;

use App\Traits\GeneratesSlug;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsPage extends Model
{
    use HasFactory, HasUuid, GeneratesSlug, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'template', 'content', 'sections',
        'excerpt', 'featured_image',
        'status', 'published_at',
        'seo_title', 'seo_description', 'seo_image',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sections'     => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Structured page-builder blocks. Named `pageSections` (not `sections`)
     * because the model also carries a `sections` JSON attribute, which would
     * otherwise shadow this relationship when accessed as a property.
     */
    public function pageSections(): HasMany
    {
        return $this->hasMany(CmsSection::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $q)
    {
        return $q->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
