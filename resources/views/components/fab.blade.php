{{-- フローティングボタン（G4「＋ 質問する」、G7「✎ 回答する」） --}}
@props(['href', 'icon' => '＋'])
<a href="{{ $href }}" {{ $attributes->class('mq-fab') }}>
    <span aria-hidden="true">{{ $icon }}</span>{{ $slot }}
</a>
