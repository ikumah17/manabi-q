<?php

namespace App\Support;

/**
 * LIKE 検索のパターン作成（% _ を文字として扱う。ESCAPE '!' と組み合わせて使う）
 * MySQL と SQLite で同じ動きにするため、エスケープ文字は ! に固定する
 */
final class Like
{
    public const ESCAPE = '!';

    /**
     * 部分一致のパターン（例：100% → %100!%%）
     */
    public static function contains(string $value): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value).'%';
    }
}
