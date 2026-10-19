<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductImage extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'product_id', 'product_variant_id', 'path', 'thumbnail_path',
        'card_path', 'gallery_path', 'alt_text', 'caption',
        'type', 'sort_order', 'is_primary', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_primary'   => 'boolean',
            'sort_order'   => 'integer',
            'metadata'     => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function url(string $size = 'card'): ?string
    {
        $column = match ($size) {
            'thumb'   => 'thumbnail_path',
            'card'    => 'card_path',
            'gallery' => 'gallery_path',
            default   => 'path',
        };
        $value = $this->{$column} ?: $this->path;
        return $value
            ? \Storage::disk('public')->url($value)
            : null;
    }
}
