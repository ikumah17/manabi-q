{{-- フローティングボタン（G4「＋ 質問する」、G7「✎ 回答する」）。icon は ＋・✎ のほか <x-icon> の名前も指定できる --}}
@props(['href', 'icon' => '＋'])
@php
    $iconName = ['＋' => 'plus', '✎' => 'pencil'][$icon] ?? $icon;
@endphp
<a href="{{ $href }}" {{ $attributes->class('mq-fab') }}>
    <x-icon :name="$iconName" />{{ $slot }}
</a>
