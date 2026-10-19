<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active      = 'active';
    case Suspended   = 'suspended';
    case Invited     = 'invited';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Active      => 'Active',
            self::Suspended   => 'Suspended',
            self::Invited     => 'Invited',
            self::Deactivated => 'Deactivated',
        };
    }

    public function canLogin(): bool
    {
        return $this === self::Active;
    }
}
