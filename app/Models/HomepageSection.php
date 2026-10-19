<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomepageSection extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'key', 'title', 'subtitle', 'content', 'configuration',
        'view', 'sort_order', 'is_published',
        'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published'   => 'boolean',
            'sort_order'     => 'integer',
            'starts_at'      => 'datetime',
            'ends_at'        => 'datetime',
            'configuration'  => 'array',
        ];
    }

    public function scopePublished(Builder $q)
    {
        return $q->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })->orderBy('sort_order');
    }
}
