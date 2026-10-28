<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id', 'type', 'threshold', 'current_stock',
        'is_resolved', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'threshold'     => 'integer',
            'current_stock' => 'integer',
            'is_resolved'   => 'boolean',
            'resolved_at'   => 'datetime',
        ];
    }

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeUnresolved(Builder $q)
    {
        return $q->where('is_resolved', false);
    }

    public function scopeOfType(Builder $q, string $type)
    {
        return $q->where('type', $type);
    }
}
