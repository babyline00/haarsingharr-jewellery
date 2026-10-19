<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft    = 'draft';
    case Active   = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Draft',
            self::Active   => 'Active',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft    => 'bg-slate-100 text-slate-700',
            self::Active   => 'bg-emerald-100 text-emerald-800',
            self::Archived => 'bg-rose-100 text-rose-800',
        };
    }
}
