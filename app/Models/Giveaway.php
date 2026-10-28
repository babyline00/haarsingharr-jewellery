<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Giveaway extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'name', 'description', 'prize', 'coupon_type', 'coupon_value',
        'coupon_min_order', 'coupon_expiry_days', 'winners_count',
        'status', 'draw_at', 'drawn_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'coupon_value'       => 'decimal:2',
            'coupon_min_order'   => 'decimal:2',
            'coupon_expiry_days' => 'integer',
            'winners_count'      => 'integer',
            'draw_at'            => 'datetime',
            'drawn_at'           => 'datetime',
        ];
    }

    public function entries(): HasMany
    {
        return $this->hasMany(GiveawayEntry::class);
    }

    public function winners(): HasMany
    {
        return $this->hasMany(GiveawayEntry::class)->where('is_winner', true);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function isDrawn(): bool
    {
        return $this->status === 'drawn' || $this->drawn_at !== null;
    }

    public function canDraw(): bool
    {
        return ! $this->isDrawn()
            && $this->status !== 'cancelled'
            && $this->entries()->count() >= $this->winners_count;
    }
}
