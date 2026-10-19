<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'payment_id', 'order_id', 'amount', 'type', 'status',
        'reason', 'notes', 'provider_ref',
        'approved_by', 'approved_at', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'approved_at'   => 'datetime',
            'processed_at'  => 'datetime',
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
        $this->update([
            'status'       => 'approved',
            'approved_by'  => $approver->id,
            'approved_at'  => now(),
            'notes'        => trim(($this->notes ?? '')."\n".$note),
        ]);
        return $this;
    }
}
