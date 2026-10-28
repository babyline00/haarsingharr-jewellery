<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiftPopupEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event', 'session_id', 'user_id', 'gift_claim_id', 'mode', 'ip_address',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(GiftClaim::class, 'gift_claim_id');
    }
}
