<?php

namespace App\Support;

use Normalizer;

/**
 * カテゴリ名の重複判定用の比較キー（G16）
 *
 * - 前後の空白（全角スペースを含む）はリクエストの TrimStrings で取り除かれている前提
 * - NFKC で正規化（全角英数→半角、半角カナ→全角、全角スペース→半角 など）し、大文字・小文字を区別しない
 * - 中の空白は詰めない（「授業」と「授 業」は別の名前）
 * - NFKC の副作用：ローマ数字「Ⅳ」→「IV」、丸数字「①」→「1」、「㈱」→「(株)」なども同じ文字として扱う
 *
 * 注意：DB の照合順序 utf8mb4_unicode_ci は、さらに「は」と「ば」、ひらがなとカタカナも同じとみなす。
 * そのためアプリの判定で別名でも、DB の UNIQUE 制約で拒否されることがある（例外を同じメッセージに変換して表示する）。
 * 照合順序は変更しない（Phase 8 の決定）。
 */
final class CategoryName
{
    public static function key(string $name): string
    {
        $normalized = Normalizer::normalize(trim($name), Normalizer::FORM_KC);

        return mb_strtolower($normalized === false ? trim($name) : $normalized, 'UTF-8');
    }
}
