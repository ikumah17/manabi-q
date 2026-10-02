{{-- ★評価の表示（ベストアンサーのみ。例：★★★★☆） --}}
@props(['rating'])
<span {{ $attributes->class('mq-stars') }} role="img" aria-label="評価 {{ $rating }} / 5">{{ str_repeat('★', $rating) }}{{ str_repeat('☆', 5 - $rating) }}</span>
