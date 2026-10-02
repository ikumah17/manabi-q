<?php

namespace App\Services\AiAnswer;

/**
 * Gemini の応答を保存用のプレーンテキストに整える
 *
 * - 簡単な Markdown 記号（``` ** __ 行頭の #）を取り除き、行頭の「* 」「- 」は「・」にする
 * - 表示上限（ai_answers.body の表示桁数 2000）で切り詰める
 * - HTML はここでは変換しない（表示時に Blade の {{ }} でエスケープする）
 */
class ResponseFormatter
{
    public const MAX_LENGTH = 2000;

    public function format(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $lines = [];
        foreach (explode("\n", $text) as $line) {
            if (preg_match('/^\s*```/u', $line)) {
                continue;
            }

            $line = preg_replace('/^\s{0,3}#{1,6}\s+/u', '', $line) ?? $line;
            $line = preg_replace('/^(\s*)[*\-]\s+/u', '$1・', $line) ?? $line;
            $line = str_replace(['**', '__'], '', $line);

            $lines[] = rtrim($line);
        }

        $text = preg_replace("/\n{3,}/u", "\n\n", implode("\n", $lines)) ?? '';

        return mb_substr(trim($text), 0, self::MAX_LENGTH);
    }
}
