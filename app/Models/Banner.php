<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name', 'location', 'image', 'mobile_image', 'alt_text',
        'link_url', 'link_text', 'heading', 'subheading',
        'configuration', 'sort_order', 'is_published',
        'starts_at', 'ends_at', 'impressions', 'clicks',
    ];

    protected function casts(): array
    {
        return [
            'is_published'   => 'boolean',
            'sort_order'     => 'integer',
            'impressions'    => 'integer',
            'clicks'         => 'integer',
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

    public function scopeAtLocation(Builder $q, string $location)
    {
        return $q->where('location', $location);
    }

    // --------------------------------------------------------------------------
    // Tracking
    // --------------------------------------------------------------------------
    public function recordImpression(): void
    {
        $this->increment('impressions');
    }

    public function recordClick(): void
    {
        $this->increment('clicks');
    }

    public function clickThroughRate(): ?float
    {
        if ($this->impressions === 0) {
            return null;
        }

        return round(($this->clicks / $this->impressions) * 100, 2);
    }
}
