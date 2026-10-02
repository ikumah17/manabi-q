<?php

namespace App\Services\Gemini;

/**
 * Gemini API の生成結果
 */
final class GeminiResult
{
    public function __construct(
        public readonly string $text,
        public readonly string $modelName,
    ) {}
}
