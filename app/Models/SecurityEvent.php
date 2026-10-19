<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityEvent extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'user_id', 'email', 'event_type', 'outcome',
        'ip_address', 'user_agent', 'description', 'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfType(Builder $q, string $type)
    {
        return $q->where('event_type', $type);
    }

    public function scopeFailed(Builder $q)
    {
        return $q->where('outcome', 'failure');
    }

    public function scopeSuspicious(Builder $q)
    {
        return $q->whereIn('outcome', ['failure', 'blocked', 'warning']);
    }

    public static function log(string $type, ?User $user, string $outcome = 'success',
        array $context = [], ?string $description = null): self
    {
        return self::create([
            'user_id'     => $user?->id,
            'email'       => $user?->email ?? ($context['email'] ?? null),
            'event_type'  => $type,
            'outcome'     => $outcome,
            'ip_address'  => request()?->ip(),
            'user_agent'  => request()?->userAgent(),
            'description' => $description ?? $type,
            'context'     => $context,
        ]);
    }
}
