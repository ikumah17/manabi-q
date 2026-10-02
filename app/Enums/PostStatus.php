<?php

namespace App\Enums;

/**
 * questions.status / answers.status（0：非表示、1：公開）
 */
enum PostStatus: int
{
    case Hidden = 0;
    case Published = 1;

    public function label(): string
    {
        return match ($this) {
            self::Hidden => '非表示',
            self::Published => '公開',
        };
    }
}
