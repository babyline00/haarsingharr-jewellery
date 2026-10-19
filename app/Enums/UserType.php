<?php

namespace App\Enums;

enum UserType: string
{
    case Customer = 'customer';
    case Staff    = 'staff';
    case Admin    = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Staff    => 'Staff Member',
            self::Admin    => 'Administrator',
        };
    }

    public function isStaffLike(): bool
    {
        return in_array($this, [self::Staff, self::Admin], true);
    }
}
