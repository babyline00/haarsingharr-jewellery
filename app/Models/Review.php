<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'product_id', 'user_id', 'customer_name', 'order_id',
        'rating', 'title', 'body',
        'is_verified_purchase', 'is_approved', 'approved_at', 'approved_by',
        'is_featured', 'admin_response',
        'helpful_count', 'report_count',
    ];

    protected function casts(): array
    {
        return [
            'rating'                => 'integer',
            'is_verified_purchase'  => 'boolean',
            'is_approved'           => 'boolean',
            'is_featured'           => 'boolean',
            'helpful_count'         => 'integer',
            'report_count'          => 'integer',
            'approved_at'           => 'datetime',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ReviewImage::class)->orderBy('sort_order');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $q)
    {
        return $q->where('is_approved', true);
    }

    public function scopeFeatured(Builder $q)
    {
        return $q->approved()->where('is_featured', true);
    }

    public function scopeVerified(Builder $q)
    {
        return $q->where('is_verified_purchase', true);
    }

    public function approve(User $approver): self
    {
        $this->update([
            'is_approved'  => true,
            'approved_by'  => $approver->id,
            'approved_at'  => now(),
        ]);
        $this->product->updateRating();
        return $this;
    }

    public function reject(): self
    {
        $this->update(['is_approved' => false]);
        $this->product->updateRating();
        return $this;
    }
}
