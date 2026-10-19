<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending            = 'pending';
    case Confirmed          = 'confirmed';
    case Processing         = 'processing';
    case Packed             = 'packed';
    case Shipped            = 'shipped';
    case Delivered          = 'delivered';
    case Cancelled          = 'cancelled';
    case Returned           = 'returned';
    case PartiallyFulfilled = 'partially_fulfilled';

    public function label(): string
    {
        return match ($this) {
            self::Pending            => 'Pending Confirmation',
            self::Confirmed          => 'Confirmed',
            self::Processing         => 'Processing',
            self::Packed             => 'Packed',
            self::Shipped            => 'Shipped',
            self::Delivered          => 'Delivered',
            self::Cancelled          => 'Cancelled',
            self::Returned           => 'Returned',
            self::PartiallyFulfilled => 'Partially Fulfilled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending            => 'bg-amber-100 text-amber-800',
            self::Confirmed          => 'bg-blue-100 text-blue-800',
            self::Processing         => 'bg-indigo-100 text-indigo-800',
            self::Packed             => 'bg-cyan-100 text-cyan-800',
            self::Shipped            => 'bg-violet-100 text-violet-800',
            self::Delivered          => 'bg-emerald-100 text-emerald-800',
            self::Cancelled          => 'bg-rose-100 text-rose-800',
            self::Returned           => 'bg-slate-100 text-slate-800',
            self::PartiallyFulfilled => 'bg-orange-100 text-orange-800',
        };
    }

    public function isCancellable(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Confirmed,
            self::Processing,
            self::Packed,
        ], true);
    }

    public function isFulfilmentStarted(): bool
    {
        return in_array($this, [
            self::Processing,
            self::Packed,
            self::Shipped,
            self::Delivered,
        ], true);
    }

    /**
     * Allowed forward transitions for state machine validation.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending            => [self::Confirmed, self::Cancelled],
            self::Confirmed          => [self::Processing, self::Packed, self::Cancelled],
            self::Processing         => [self::Packed, self::Cancelled],
            self::Packed             => [self::Shipped, self::Cancelled],
            self::Shipped            => [self::Delivered, self::Returned, self::PartiallyFulfilled],
            self::Delivered          => [self::Returned],
            self::Returned           => [],
            self::Cancelled         => [],
            self::PartiallyFulfilled => [self::Shipped, self::Delivered, self::Cancelled],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
