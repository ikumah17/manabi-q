<?php

namespace App\Enums;

/**
 * reports.reason（表記は G17 に統一：Q18）
 */
enum ReportReason: int
{
    case Inappropriate = 1;
    case Harassment = 2;
    case Spam = 3;
    case PersonalInfo = 4;
    case Other = 5;

    public function label(): string
    {
        return match ($this) {
            self::Inappropriate => '不適切な内容',
            self::Harassment => '誹謗中傷・嫌がらせ',
            self::Spam => 'スパム・宣伝',
            self::PersonalInfo => '個人情報の掲載',
            self::Other => 'その他',
        };
    }
}
