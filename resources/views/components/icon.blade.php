{{--
    アイコン（インライン SVG。24px の枠・線幅 1.75・角は丸め・色は文字色 currentColor）
    - name  : アイコン名（下の $paths のキー）。SVG に data-icon="名前" を付ける
    - label : 読み上げ用の文言。指定すると role="img" と aria-label を付ける（装飾なら省略して aria-hidden）
--}}
@props(['name', 'label' => null])
@php
    $paths = [
        'chevron-left' => '<path d="M15 18l-6-6 6-6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'home' => '<path d="M3.5 10.5L12 3.5l8.5 7"/><path d="M5.5 9v10.5a1 1 0 0 0 1 1H10v-6h4v6h3.5a1 1 0 0 0 1-1V9"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'square-plus' => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><path d="M12 8.5v7M8.5 12h7"/>',
        'trophy' => '<path d="M8 4h8v5a4 4 0 0 1-8 0V4z"/><path d="M8 5.5H5.5a2.5 2.5 0 0 0 2.6 4.4M16 5.5h2.5a2.5 2.5 0 0 1-2.6 4.4"/><path d="M12 13v4M8.5 20.5h7M9.5 17h5"/>',
        'person' => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="M20 20l-4.2-4.2"/>',
        'pencil' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4z"/><path d="M13.5 6.5l4 4"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'flag' => '<path d="M5.5 21V4"/><path d="M5.5 4.5h11l-2 4 2 4h-11"/>',
        'lock' => '<rect x="5" y="10.5" width="14" height="10" rx="2.5"/><path d="M8.5 10.5V7.5a3.5 3.5 0 0 1 7 0v3"/>',
        'star' => '<path d="M12 3.5l2.6 5.3 5.9.9-4.25 4.1 1 5.8L12 16.85 6.75 19.6l1-5.8L3.5 9.7l5.9-.9z"/>',
        'sparkle' => '<path d="M11 4l1.7 4.8L17.5 10.5l-4.8 1.7L11 17l-1.7-4.8L4.5 10.5l4.8-1.7z"/><path d="M18 3.5v3.5M16.25 5.25h3.5"/>',
        'chat-question' => '<path d="M4.5 5h15a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H11l-4.5 3.5V17h-2a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z"/><path d="M9.75 9.25a2.25 2.25 0 1 1 3.1 2.08c-.5.22-.85.7-.85 1.25v.17"/><path d="M12 14.75h.01"/>',
        'inbox' => '<path d="M3.5 13.5L6.5 5h11l3 8.5"/><path d="M3.5 13.5V19a1 1 0 0 0 1 1h15a1 1 0 0 0 1-1v-5.5h-5l-1.5 2.5h-4l-1.5-2.5h-5z"/>',
    ];
@endphp
@isset($paths[$name])
    <svg {{ $attributes->class('mq-icon') }} data-icon="{{ $name }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
         fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
         @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif>{!! $paths[$name] !!}</svg>
@endisset
