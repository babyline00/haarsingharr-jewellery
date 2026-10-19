<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterSubscription extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'email', 'token', 'status', 'user_id', 'subscribed_at',
        'unsubscribed_at', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at'   => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $q)
    {
        return $q->where('status', 'subscribed');
    }

    public static function subscribe(string $email, ?User $user = null, ?string $ip = null): self
    {
        $sub = static::firstOrCreate(
            ['email' => $email],
            [
                'user_id'        => $user?->id,
                'token'          => hash_hmac('sha256', $email.time(), config('app.key')),
                'status'         => 'pending',
                'ip_address'     => $ip,
                'subscribed_at'  => now(),
            ]
        );

        if ($sub->status !== 'subscribed' || $sub->status === 'pending') {
            $sub->update([
                'status' => 'subscribed',
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
            ]);
        }
        return $sub;
    }
}
