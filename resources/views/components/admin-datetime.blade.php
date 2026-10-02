{{-- 管理画面の日時（G18：今年は「10/01 12:05」、それ以外は「2025/10/01 12:05」） --}}
@props(['at', 'dateOnly' => false])
@php
    $format = $at->isCurrentYear() ? 'm/d' : 'Y/m/d';
    $format .= $dateOnly ? '' : ' H:i';
@endphp
<time datetime="{{ $at->toIso8601String() }}" {{ $attributes }}>{{ $at->format($format) }}</time>