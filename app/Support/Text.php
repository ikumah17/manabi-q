<?php

namespace App\Support;

/**
 * 文字数基準の切り詰め（mb_substr ベース）
 *
 * Str::limit は表示幅（全角＝2）で数えるため、設計書の「〇文字」と合わない。
 * こちらは全角・半角・絵文字（1つのコードポイント）をすべて1文字として数える。
 * 注意：結合文字・ZWJ でつないだ絵文字（例：家族の絵文字）は複数のコードポイントとして数える。
 */
final class Text
{
    public const ELLIPSIS = '…';

    public static function truncate(?string $text, int $length, string $end = self::ELLIPSIS): string
    {
        $text ??= '';

        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $length, 'UTF-8')).$end;
    }
}
