<?php

namespace App\Enums;

/**
 * users.role（0：一般ユーザー、1：管理者）
 */
enum UserRole: int
{
    case User = 0;
    case Admin = 1;

    public function label(): string
    {
        return match ($this) {
            self::User => '一般',
            self::Admin => '管理者',
        };
    }
}
