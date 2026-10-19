<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid              = 'unpaid';
    case Pending             = 'pending';
    case Paid                = 'paid';
    case Failed              = 'failed';
    case PartiallyRefunded   = 'partially_refunded';
    case Refunded            = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid            => 'Unpaid',
            self::Pending           => 'Pending',
            self::Paid              => 'Paid',
            self::Failed            => 'Failed',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Refunded          => 'Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Unpaid            => 'bg-slate-100 text-slate-700',
            self::Pending           => 'bg-amber-100 text-amber-800',
            self::Paid               => 'bg-emerald-100 text-emerald-800',
            self::Failed             => 'bg-rose-100 text-rose-800',
            self::PartiallyRefunded  => 'bg-orange-100 text-orange-800',
            self::Refunded           => 'bg-blue-100 text-blue-800',
        };
    }
}
