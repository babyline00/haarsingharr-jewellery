<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsPageRevision extends Model
{
    protected $fillable = [
        'cms_page_id', 'user_id', 'title', 'content',
        'sections', 'seo_title', 'seo_description',
        'status', 'published_at', 'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'sections'     => 'array',
            'snapshot'     => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
