<?php

namespace App\Services\AiAnswer;

/**
 * AI回答の生成結果と、画面に出すメッセージ（質問者本人のみに表示）
 */
enum AiAnswerResult
{
    case Created;
    case AlreadyExists;
    case Skipped;
    case Busy;
    case LimitReached;
    case Failed;

    public const FAILED_MESSAGE = 'AI回答を取得できませんでした';

    public const LIMIT_MESSAGE = 'AI回答の利用上限に達しました。時間をおいて再試行してください';

    public const BUSY_MESSAGE = 'AI回答を作成中です。しばらくしてから再度お試しください';

    public function message(): ?string
    {
        return match ($this) {
            self::Failed => self::FAILED_MESSAGE,
            self::LimitReached => self::LIMIT_MESSAGE,
            self::Busy => self::BUSY_MESSAGE,
            default => null,
        };
    }
}
