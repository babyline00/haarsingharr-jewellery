<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNote extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'order_id', 'user_id', 'body', 'is_customer_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_customer_visible' => 'boolean',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function authorName(): string
    {
        return $this->user?->name ?? 'System';
    }
}
