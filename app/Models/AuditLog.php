<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'actor_id', 'actor_email', 'action', 'module',
        'target_type', 'target_id',
        'properties', 'previous', 'after',
        'ip_address', 'user_agent', 'outcome', 'note',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'previous'   => 'array',
            'after'       => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // --------------------------------------------------------------------------
    // Audit records are append-only. Disable updates and deletes at the model layer.
    // --------------------------------------------------------------------------
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \RuntimeException('Audit log entries are immutable.');
    }

    public function delete(): ?bool
    {
        throw new \RuntimeException('Audit log entries cannot be deleted.');
    }

    public function scopeForAction(Builder $q, string $action)
    {
        return $q->where('action', $action);
    }

    public function scopeForModule(Builder $q, string $module)
    {
        return $q->where('module', $module);
    }

    public function scopeForTarget(Builder $q, string $type, int $id)
    {
        return $q->where('target_type', $type)->where('target_id', $id);
    }

    public function scopeByActor(Builder $q, int $userId)
    {
        return $q->where('actor_id', $userId);
    }
}
