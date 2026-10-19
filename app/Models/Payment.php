<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'order_id', 'provider', 'method', 'status',
        'amount', 'amount_captured', 'amount_refunded', 'currency',
        'provider_transaction_ref', 'provider_payment_id',
        'provider_merchant_id', 'provider_status',
        'provider_payload', 'provider_response',
        'initiated_at', 'authorized_at', 'captured_at', 'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'              => 'decimal:2',
            'amount_captured'     => 'decimal:2',
            'amount_refunded'     => 'decimal:2',
            'provider_payload'    => 'array',
            'provider_response'   => 'array',
            'initiated_at'        => 'datetime',
            'authorized_at'       => 'datetime',
            'captured_at'         => 'datetime',
            'failed_at'           => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(PaymentEvent::class)->latest();
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function markCaptured(): void
    {
        $this->update([
            'status'          => 'captured',
            'captured_at'     => now(),
            'amount_captured' => $this->amount,
        ]);
        $this->order->update([
            'paid_amount'        => $this->order->paid_amount + $this->amount,
            'payment_status'     => 'paid',
        ]);
    }

    public function markFailed(string $reason = null): void
    {
        $this->update([
            'status'    => 'failed',
            'failed_at' => now(),
        ]);
        $this->events()->create([
            'type'   => 'failed',
            'status' => 'failed',
            'note'   => $reason,
        ]);
    }

    public function isCaptured(): bool
    {
        return $this->status === 'captured';
    }
}
