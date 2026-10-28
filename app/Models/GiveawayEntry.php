<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiveawayEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'giveaway_id', 'user_id', 'gift_claim_id', 'is_winner', 'drawn_at', 'added_by',
    ];

    protected function casts(): array
    {
        return [
            'is_winner' => 'boolean',
            'drawn_at'  => 'datetime',
        ];
    }

    public function giveaway(): BelongsTo
    {
        return $this->belongsTo(Giveaway::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(GiftClaim::class, 'gift_claim_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
