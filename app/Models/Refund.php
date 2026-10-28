<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Refund extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'payment_id', 'order_id', 'amount', 'type', 'status',
        'reason', 'notes', 'provider_ref', 'restock_items',
        'approved_by', 'approved_at', 'processed_at', 'restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'restock_items' => 'boolean',
            'approved_at'   => 'datetime',
            'processed_at'  => 'datetime',
            'restocked_at'  => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function approve(User $approver, ?string $note = null): self
    {
        if (! $this->canBeApproved()) {
            throw new \DomainException('Refund is not in an approvable state.');
        }

        $notes = trim(($this->notes ?? '')."\n".($note ? "Approved: {$note}" : ''));

        $this->update([
            'status'       => 'approved',
            'approved_by'  => $approver->id,
            'approved_at'  => now(),
            'notes'        => $notes !== '' ? $notes : null,
        ]);

        return $this;
    }

    public function reject(User $actor, ?string $note = null): self
    {
        if (! in_array($this->status, ['pending', 'approved', 'processing'], true)) {
            throw new \DomainException('Refund cannot be rejected in its current state.');
        }

        $notes = trim(($this->notes ?? '')."\n".($note ? "Rejected: {$note}" : 'Rejected.'));

        $this->update([
            'status'       => 'cancelled',
            'notes'        => $notes,
            'processed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Finalise a refund. Safe to call once: totals are recomputed from the
     * completed refunds rather than incremented, so retries never double-count.
     */
    public function markCompleted(?string $reference = null, ?string $note = null): self
    {
        if ($this->status === 'completed') {
            return $this;
        }

        return DB::transaction(function () use ($reference, $note) {
            $notes = trim(($this->notes ?? '')."\n".($note ? "Completed: {$note}" : ''));

            $this->update([
                'status'       => 'completed',
                'processed_at' => now(),
                'provider_ref' => $reference ?: $this->provider_ref,
                'notes'        => $notes !== '' ? $notes : null,
            ]);

            $payment = $this->payment()->first();
            $order = $this->order()->first();

            if ($payment) {
                $paymentRefunded = (float) $payment->refunds()
                    ->where('status', 'completed')
                    ->sum('amount');

                $payment->update([
                    'amount_refunded' => $paymentRefunded,
                    'status' => $paymentRefunded >= (float) $payment->amount
                        ? 'refunded'
                        : ($paymentRefunded > 0 ? 'partially_refunded' : $payment->status),
                ]);
            }

            if ($order) {
                $orderRefunded = (float) $order->refunds()
                    ->where('status', 'completed')
                    ->sum('amount');

                $order->update(['refunded_amount' => $orderRefunded]);
                $payment?->syncOrderTotals();
            }

            return $this->refresh();
        });
    }
}
