{{-- 回答に添付されたスタンプ（装飾のみ。G7 ④⑤「★ 50回答達成」） --}}
@props(['stamp'])
<span {{ $attributes->class('mq-stamp-chip') }}><span class="mq-stamp-chip__star" aria-hidden="true">★</span> {{ $stamp->name }}</span>
