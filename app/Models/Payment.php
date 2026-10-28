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
        if ($this->status === 'captured') {
            return;
        }

        $this->update([
            'status'          => 'captured',
            'captured_at'     => now(),
            'amount_captured' => $this->amount,
        ]);

        $this->syncOrderTotals();
    }

    public function markFailed(?string $reason = null): void
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

    /**
     * Recompute the order's paid amount from captured payments, while
     * preserving refund-driven statuses.
     */
    public function syncOrderTotals(): void
    {
        $order = $this->order()->first();

        if (! $order) {
            return;
        }

        $captured = (float) $order->payments()
            ->whereIn('status', ['captured', 'partially_refunded', 'refunded'])
            ->sum('amount_captured');

        $paid = min((float) $order->grand_total, $captured);
        $refunded = (float) $order->refunded_amount;
        $total = (float) $order->grand_total;

        $status = match (true) {
            $refunded > 0 && $refunded >= $total => 'refunded',
            $refunded > 0 => 'partially_refunded',
            $paid >= $total && $total > 0 => 'paid',
            $paid > 0 => 'pending',
            default => 'unpaid',
        };

        $order->update([
            'paid_amount' => $paid,
            'payment_status' => $status,
        ]);
    }

    public function isCaptured(): bool
    {
        return $this->status === 'captured';
    }
}
