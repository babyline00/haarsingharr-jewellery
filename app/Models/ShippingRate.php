<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'shipping_zone_id', 'name', 'method',
        'min_order_total', 'rate', 'free_over',
        'min_weight_grams', 'max_weight_grams',
        'eta_min_days', 'eta_max_days',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_order_total' => 'decimal:2',
            'rate' => 'decimal:2',
            'free_over' => 'decimal:2',
            'min_weight_grams' => 'decimal:2',
            'max_weight_grams' => 'decimal:2',
            'eta_min_days' => 'integer',
            'eta_max_days' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function etaLabel(): string
    {
        if ($this->eta_min_days === null && $this->eta_max_days === null) {
            return '—';
        }

        $min = $this->eta_min_days ?? 0;
        $max = $this->eta_max_days ?? $min;

        return $min === $max
            ? "{$min} day".($min === 1 ? '' : 's')
            : "{$min}–{$max} days";
    }

    public function appliesTo(float $subtotal, ?float $weightGrams = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->min_order_total !== null && $subtotal < (float) $this->min_order_total) {
            return false;
        }

        if ($weightGrams !== null) {
            if ($this->min_weight_grams !== null && $weightGrams < (float) $this->min_weight_grams) {
                return false;
            }

            if ($this->max_weight_grams !== null && $weightGrams > (float) $this->max_weight_grams) {
                return false;
            }
        }

        return true;
    }

    public function quote(float $subtotal): float
    {
        if ($this->free_over !== null && $subtotal >= (float) $this->free_over) {
            return 0.0;
        }

        return (float) $this->rate;
    }
}
