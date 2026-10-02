{{-- 下部タブメニュー（G4 ①：ホーム / 質問する / ランキング / マイページ） --}}
@props(['active' => null])
@php
    $items = [
        'home' => ['label' => 'ホーム', 'icon' => '◆', 'route' => 'home'],
        'ask' => ['label' => '質問する', 'icon' => '＋', 'route' => 'questions.create'],
        'ranking' => ['label' => 'ランキング', 'icon' => '★', 'route' => 'ranking'],
        'mypage' => ['label' => 'マイページ', 'icon' => '●', 'route' => 'mypage'],
    ];
@endphp
<nav class="mq-tabbar" aria-label="メインメニュー">
    @foreach ($items as $key => $item)
        <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
           @class(['mq-tabbar__item', 'is-active' => $active === $key])
           @if ($active === $key) aria-current="page" @endif>
            <span class="mq-tabbar__icon" aria-hidden="true">{{ $item['icon'] }}</span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
