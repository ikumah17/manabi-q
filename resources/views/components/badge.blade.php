{{--
    バッジ（カテゴリ・状態・種別）
    variant: primary（カテゴリ・質問）/ danger（未解決・未対応・利用停止）/ success（解決済み・回答・公開・利用中・対応済み）
             / ai（AI回答あり）/ muted（非表示）/ best（ベストアンサー）
--}}
@props(['variant' => 'primary'])
<span {{ $attributes->class(['mq-badge', 'mq-badge--'.$variant]) }}>{{ $slot }}</span>
