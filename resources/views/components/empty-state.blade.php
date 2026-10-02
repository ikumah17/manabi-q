{{--
    空状態（一覧が0件のとき）：アイコン＋文言（スロット。既存の文言）＋補助文
    - icon : <x-icon> の名前
    - sub  : 補助文（省略可）
--}}
@props(['icon' => 'inbox', 'sub' => null])
<div {{ $attributes->class('mq-empty-state') }}>
    <span class="mq-empty-state__icon"><x-icon :name="$icon" /></span>
    <p class="mq-empty-state__text">{{ $slot }}</p>
    @if ($sub)
        <p class="mq-empty-state__sub">{{ $sub }}</p>
    @endif
</div>
