<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'product_variant_id', 'product_id',
        'type', 'direction',
        'quantity_change',
        'previous_stock', 'new_stock',
        'reserved_change', 'previous_reserved', 'new_reserved',
        'reason', 'notes', 'reference_type', 'reference_id',
        'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change'    => 'integer',
            'previous_stock'     => 'integer',
            'new_stock'          => 'integer',
            'reserved_change'    => 'integer',
            'previous_reserved'  => 'integer',
            'new_reserved'       => 'integer',
        ];
    }

    // --------------------------------------------------------------------------
    // Immutable ledger — disable updates once created.
    // --------------------------------------------------------------------------
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException('InventoryMovement entries are immutable.');
        }
        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new \RuntimeException('InventoryMovement entries cannot be deleted.');
    }

    // --------------------------------------------------------------------------
    // Relationships
    // --------------------------------------------------------------------------
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // --------------------------------------------------------------------------
    // Scopes
    // --------------------------------------------------------------------------
    public function scopeForVariant(Builder $q, int $variantId)
    {
        return $q->where('product_variant_id', $variantId);
    }

    public function scopeOfDirection(Builder $q, string $direction)
    {
        return $q->where('direction', $direction);
    }

    public function scopeOfType(Builder $q, string $type)
    {
        return $q->where('type', $type);
    }
}
