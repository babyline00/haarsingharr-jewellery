<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'user_id', 'customer_name',
        'question', 'answer', 'is_approved',
        'answered_by', 'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'answered_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answerer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function scopeApproved(Builder $q): Builder
    {
        return $q->where('is_approved', true);
    }

    public function scopeAnswered(Builder $q): Builder
    {
        return $q->whereNotNull('answer');
    }

    public function scopeUnanswered(Builder $q): Builder
    {
        return $q->whereNull('answer')->orWhere('answer', '');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('is_approved', false);
    }

    public function askerEmail(): ?string
    {
        return $this->user?->email;
    }

    public function isAnswered(): bool
    {
        return filled($this->answer);
    }

    public function submitAnswer(string $answer, User $staff): self
    {
        $this->update([
            'answer'      => $answer,
            'answered_by' => $staff->id,
            'answered_at' => now(),
        ]);

        return $this;
    }

    public function approve(): self
    {
        $this->update(['is_approved' => true]);

        return $this;
    }

    public function reject(): self
    {
        $this->update(['is_approved' => false]);

        return $this;
    }
}
