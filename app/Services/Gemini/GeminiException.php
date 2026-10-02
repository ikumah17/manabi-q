<?php

namespace App\Services\Gemini;

use RuntimeException;

/**
 * Gemini API の失敗。メッセージには失敗の種類と HTTP ステータスだけを入れる
 * （API キー・URL・プロンプト・応答本文は含めない）
 */
class GeminiException extends RuntimeException
{
    public function __construct(
        public readonly GeminiFailure $failure,
        public readonly ?int $status = null,
    ) {
        parent::__construct('Gemini request failed: '.$failure->value.($status !== null ? " (HTTP {$status})" : ''));
    }
}
