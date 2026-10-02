{{-- 下部タブメニュー（G4 ①：ホーム / 質問する / ランキング / マイページ） --}}
@props(['active' => null])
@php
    $items = [
        'home' => ['label' => 'ホーム', 'icon' => 'home', 'route' => 'home'],
        'ask' => ['label' => '質問する', 'icon' => 'square-plus', 'route' => 'questions.create'],
        'ranking' => ['label' => 'ランキング', 'icon' => 'trophy', 'route' => 'ranking'],
        'mypage' => ['label' => 'マイページ', 'icon' => 'person', 'route' => 'mypage'],
    ];
@endphp
<nav class="mq-tabbar" aria-label="メインメニュー">
    @foreach ($items as $key => $item)
        <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
           @class(['mq-tabbar__item', 'is-active' => $active === $key])
           @if ($active === $key) aria-current="page" @endif>
            <x-icon :name="$item['icon']" class="mq-tabbar__icon" />
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>
