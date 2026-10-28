<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentEvent extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'payment_id', 'type', 'provider_event_id',
        'status', 'amount', 'currency', 'payload',
        'signature', 'is_verified', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'decimal:2',
            'is_verified' => 'boolean',
            'payload'     => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Idempotency check: a webhook with the same provider_event_id must
     * only be processed once. Use firstOrCreate when handling events.
     */
    public function scopeAlreadyProcessed(Builder $q, string $provider, string $eventId): bool
    {
        return $q->where('provider_event_id', $eventId)->exists();
    }
}
