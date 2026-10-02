{{--
    一般ユーザー画面の共通レイアウト（スマートフォン向け・390px 基準）
    - header : ヘッダーの表示名（既定は「まなびQ」）
    - title  : ブラウザのタブに出すページ名（既定は header）
    - back   : 左上の ‹ の戻り先 URL（null なら表示しない）
    - tab    : 下部タブで選択中の項目（home / ask / ranking / mypage。null ならタブを出さない）
    - スロット fab    : フローティングボタン
    - スロット sheets : ボトムシート（G9・G17）
--}}
@props([
    'header' => 'まなびQ',
    'title' => null,
    'back' => null,
    'tab' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title ?? $header])
</head>
<body class="mq-mobile">
    <div @class(['mq-app', 'mq-app--with-tabbar' => $tab])>
        <header class="mq-header">
            @if ($back)
                <a href="{{ $back }}" class="mq-header__back" aria-label="戻る">‹</a>
            @endif
            <div class="mq-header__title">{{ $header }}</div>
        </header>

        <main class="mq-main">
            <x-flash class="mb-3" />
            {{ $slot }}
        </main>

        {{ $fab ?? '' }}

        @if ($tab)
            <x-tab-bar :active="$tab" />
        @endif
    </div>

    {{ $sheets ?? '' }}
</body>
</html>
