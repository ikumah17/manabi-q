{{--
    投稿日時（一般画面）：今年は「10/01 09:12」、それ以外は「2025/10/01 09:12」
    date-only のときは「10/01」「2025/10/01」（G11 の一覧）
--}}
@props(['at', 'dateOnly' => false])
@php
    $format = $at->isCurrentYear() ? 'm/d' : 'Y/m/d';
    $format .= $dateOnly ? '' : ' H:i';
@endphp
<time datetime="{{ $at->toIso8601String() }}" {{ $attributes }}>{{ $at->format($format) }}</time>
