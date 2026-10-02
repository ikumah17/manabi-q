<?php

namespace App\Enums;

/**
 * reports.status（0：未対応、1：対応済み）
 */
enum ReportStatus: int
{
    case Pending = 0;
    case Resolved = 1;

    public function label(): string
    {
        return match ($this) {
            self::Pending => '未対応',
            self::Resolved => '対応済み',
        };
    }
}
