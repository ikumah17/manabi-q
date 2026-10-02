<?php

namespace App\Enums;

/**
 * users.status（0：利用停止、1：利用中）
 */
enum UserStatus: int
{
    case Suspended = 0;
    case Active = 1;

    public function label(): string
    {
        return match ($this) {
            self::Suspended => '利用停止',
            self::Active => '利用中',
        };
    }
}
