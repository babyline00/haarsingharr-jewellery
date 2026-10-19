<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CmsSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'cms_page_id', 'key', 'title', 'content', 'data', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'data'       => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }
}
