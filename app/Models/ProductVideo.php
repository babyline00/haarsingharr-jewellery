<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVideo extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'product_id', 'path', 'thumbnail_path', 'title',
        'type', 'sort_order', 'is_primary', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
            'metadata'   => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): ?string
    {
        return $this->path
            ? \Storage::disk('public')->url($this->path)
            : null;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path
            ? \Storage::disk('public')->url($this->thumbnail_path)
            : null;
    }
}
