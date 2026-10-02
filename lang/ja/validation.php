<?php

return [

    /*
    |--------------------------------------------------------------------------
    | バリデーションメッセージ（日本語）
    |--------------------------------------------------------------------------
    |
    | 設計書の表記（例:「タイトルを入力してください」）に合わせた文言。
    | 画面ごとに異なる文言が必要な場合は FormRequest の messages() で上書きする。
    |
    */

    'accepted' => ':attributeを承認してください',
    'accepted_if' => ':otherが:valueの場合、:attributeを承認してください',
    'active_url' => ':attributeは有効なURLではありません',
    'after' => ':attributeには:dateより後の日付を指定してください',
    'after_or_equal' => ':attributeには:date以降の日付を指定してください',
    'alpha' => ':attributeは英字のみで入力してください',
    'alpha_dash' => ':attributeは英数字・ハイフン・アンダースコアのみで入力してください',
    'alpha_num' => ':attributeは半角英数字のみで入力してください',
    'any_of' => ':attributeの値が正しくありません',
    'array' => ':attributeは配列で指定してください',
    'array_keys' => ':attributeには次の項目が必要です：:values',
    'ascii' => ':attributeは半角英数字と記号のみで入力してください',
    'before' => ':attributeには:dateより前の日付を指定してください',
    'before_or_equal' => ':attributeには:date以前の日付を指定してください',
    'between' => [
        'array' => ':attributeは:min〜:max個で指定してください',
        'file' => ':attributeは:min〜:maxKBのファイルを指定してください',
        'numeric' => ':attributeは:min〜:maxの範囲で入力してください',
        'string' => ':attributeは:min〜:max文字で入力してください',
    ],
    'boolean' => ':attributeの値が正しくありません',
    'can' => ':attributeに許可されていない値が含まれています',
    'confirmed' => ':attributeが確認用と一致しません',
    'contains' => ':attributeに必要な値が含まれていません',
    'current_password' => 'パスワードが正しくありません',
    'date' => ':attributeは正しい日付ではありません',
    'date_equals' => ':attributeには:dateと同じ日付を指定してください',
    'date_format' => ':attributeは:format形式で入力してください',
    'decimal' => ':attributeは小数点以下:decimal桁で入力してください',
    'declined' => ':attributeを拒否してください',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否してください',
    'different' => ':attributeと:otherには異なる値を入力してください',
    'digits' => ':attributeは:digits桁で入力してください',
    'digits_between' => ':attributeは:min〜:max桁で入力してください',
    'dimensions' => ':attributeの画像サイズが正しくありません',
    'distinct' => ':attributeに重複した値があります',
    'doesnt_contain' => ':attributeに次の値を含めないでください：:values',
    'doesnt_end_with' => ':attributeの末尾に次の値は使えません：:values',
    'doesnt_start_with' => ':attributeの先頭に次の値は使えません：:values',
    'email' => ':attributeを正しい形式で入力してください',
    'encoding' => ':attributeは:encodingでエンコードしてください',
    'ends_with' => ':attributeは次のいずれかで終わる必要があります：:values',
    'enum' => '選択された:attributeは正しくありません',
    'exists' => '選択された:attributeは正しくありません',
    'extensions' => ':attributeは次の拡張子のファイルを指定してください：:values',
    'file' => ':attributeにはファイルを指定してください',
    'filled' => ':attributeを入力してください',
    'gt' => [
        'array' => ':attributeは:value個より多く指定してください',
        'file' => ':attributeは:valueKBより大きいファイルを指定してください',
        'numeric' => ':attributeは:valueより大きい値を入力してください',
        'string' => ':attributeは:value文字より多く入力してください',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上指定してください',
        'file' => ':attributeは:valueKB以上のファイルを指定してください',
        'numeric' => ':attributeは:value以上の値を入力してください',
        'string' => ':attributeは:value文字以上で入力してください',
    ],
    'hex_color' => ':attributeは正しい16進数カラーコードで入力してください',
    'image' => ':attributeには画像を指定してください',
    'in' => '選択された:attributeは正しくありません',
    'in_array' => ':attributeが:otherに含まれていません',
    'in_array_keys' => ':attributeには次のいずれかのキーが必要です：:values',
    'integer' => ':attributeは整数で入力してください',
    'ip' => ':attributeは正しいIPアドレスで入力してください',
    'ipv4' => ':attributeは正しいIPv4アドレスで入力してください',
    'ipv6' => ':attributeは正しいIPv6アドレスで入力してください',
    'json' => ':attributeは正しいJSON文字列で入力してください',
    'list' => ':attributeはリスト形式で指定してください',
    'lowercase' => ':attributeは小文字で入力してください',
    'lt' => [
        'array' => ':attributeは:value個より少なく指定してください',
        'file' => ':attributeは:valueKBより小さいファイルを指定してください',
        'numeric' => ':attributeは:valueより小さい値を入力してください',
        'string' => ':attributeは:value文字より少なく入力してください',
    ],
    'lte' => [
        'array' => ':attributeは:value個以下で指定してください',
        'file' => ':attributeは:valueKB以下のファイルを指定してください',
        'numeric' => ':attributeは:value以下の値を入力してください',
        'string' => ':attributeは:value文字以下で入力してください',
    ],
    'mac_address' => ':attributeは正しいMACアドレスで入力してください',
    'max' => [
        'array' => ':attributeは:max個以下で指定してください',
        'file' => ':attributeは:maxKB以下のファイルを指定してください',
        'numeric' => ':attributeは:max以下の値を入力してください',
        'string' => ':attributeは:max文字以内で入力してください',
    ],
    'max_digits' => ':attributeは:max桁以下で入力してください',
    'mimes' => ':attributeは次の形式のファイルを指定してください：:values',
    'mimetypes' => ':attributeは次の形式のファイルを指定してください：:values',
    'min' => [
        'array' => ':attributeは:min個以上指定してください',
        'file' => ':attributeは:minKB以上のファイルを指定してください',
        'numeric' => ':attributeは:min以上の値を入力してください',
        'string' => ':attributeは:min文字以上で入力してください',
    ],
    'min_digits' => ':attributeは:min桁以上で入力してください',
    'missing' => ':attributeは指定できません',
    'missing_if' => ':otherが:valueの場合、:attributeは指定できません',
    'missing_unless' => ':otherが:valueでない場合、:attributeは指定できません',
    'missing_with' => ':valuesがある場合、:attributeは指定できません',
    'missing_with_all' => ':valuesがある場合、:attributeは指定できません',
    'multiple_of' => ':attributeは:valueの倍数で入力してください',
    'not_in' => '選択された:attributeは正しくありません',
    'not_regex' => ':attributeの形式が正しくありません',
    'numeric' => ':attributeは数値で入力してください',
    'password' => [
        'letters' => ':attributeには英字を1文字以上含めてください',
        'mixed' => ':attributeには大文字と小文字をそれぞれ1文字以上含めてください',
        'numbers' => ':attributeには数字を1文字以上含めてください',
        'symbols' => ':attributeには記号を1文字以上含めてください',
        'uncompromised' => 'この:attributeは漏えいした可能性があります。別の:attributeを指定してください',
    ],
    'present' => ':attributeが存在しません',
    'present_if' => ':otherが:valueの場合、:attributeが必要です',
    'present_unless' => ':otherが:valueでない場合、:attributeが必要です',
    'present_with' => ':valuesがある場合、:attributeが必要です',
    'present_with_all' => ':valuesがある場合、:attributeが必要です',
    'prohibited' => ':attributeは指定できません',
    'prohibited_if' => ':otherが:valueの場合、:attributeは指定できません',
    'prohibited_if_accepted' => ':otherを承認した場合、:attributeは指定できません',
    'prohibited_if_declined' => ':otherを拒否した場合、:attributeは指定できません',
    'prohibited_unless' => ':otherが:valuesでない場合、:attributeは指定できません',
    'prohibits' => ':attributeがある場合、:otherは指定できません',
    'regex' => ':attributeの形式が正しくありません',
    'required' => ':attributeを入力してください',
    'required_array_keys' => ':attributeには次の項目が必要です：:values',
    'required_if' => ':otherが:valueの場合、:attributeを入力してください',
    'required_if_accepted' => ':otherを承認した場合、:attributeを入力してください',
    'required_if_declined' => ':otherを拒否した場合、:attributeを入力してください',
    'required_unless' => ':otherが:valuesでない場合、:attributeを入力してください',
    'required_with' => ':valuesを入力する場合、:attributeも入力してください',
    'required_with_all' => ':valuesを入力する場合、:attributeも入力してください',
    'required_without' => ':valuesを入力しない場合、:attributeを入力してください',
    'required_without_all' => ':valuesをすべて入力しない場合、:attributeを入力してください',
    'same' => ':attributeと:otherが一致しません',
    'size' => [
        'array' => ':attributeは:size個で指定してください',
        'file' => ':attributeは:sizeKBのファイルを指定してください',
        'numeric' => ':attributeは:sizeを指定してください',
        'string' => ':attributeは:size文字で入力してください',
    ],
    'starts_with' => ':attributeは次のいずれかで始まる必要があります：:values',
    'string' => ':attributeは文字列で入力してください',
    'timezone' => ':attributeは正しいタイムゾーンを指定してください',
    'unique' => 'この:attributeは既に登録されています',
    'uploaded' => ':attributeのアップロードに失敗しました',
    'uppercase' => ':attributeは大文字で入力してください',
    'url' => ':attributeは正しいURLの形式で入力してください',
    'ulid' => ':attributeは正しいULIDで入力してください',
    'uuid' => ':attributeは正しいUUIDで入力してください',

    /*
    |--------------------------------------------------------------------------
    | 項目ごとの個別メッセージ
    |--------------------------------------------------------------------------
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | 項目名（画面設計書のラベルに合わせる）
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'name' => 'ユーザー名',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード（確認）',
        'current_password' => '現在のパスワード',
        'new_password' => '新しいパスワード',
        'title' => 'タイトル',
        'category_id' => 'カテゴリ',
        'body' => '本文',
        'use_ai' => 'AI即時回答',
        'stamp_id' => 'スタンプ',
        'rating' => '評価',
        'reason' => '通報理由',
        'detail' => '詳細',
        'keyword' => 'キーワード',
        'sort_order' => '表示順',
    ],

];
