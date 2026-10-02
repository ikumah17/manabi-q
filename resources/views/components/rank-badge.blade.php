{{-- 順位の丸（1位 金・2位 銀・3位 銅・4位以降 灰色） --}}
@props(['rank'])
<span {{ $attributes->class(['mq-rank-badge', 'mq-rank-badge--'.$rank => $rank <= 3]) }}>{{ $rank }}</span>
