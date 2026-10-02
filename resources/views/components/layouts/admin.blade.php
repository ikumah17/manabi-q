{{--
    管理者画面の共通レイアウト（PC 向け・1280px 基準）
    - title  : 画面タイトル（見出しとブラウザのタブに表示）
    - active : 管理メニューで選択中の項目（dashboard / posts / users / categories / reports）
--}}
@props([
    'title',
    'active' => null,
])
@php
    $menu = [
        'dashboard' => ['label' => 'ダッシュボード', 'route' => 'admin.dashboard'],
        'posts' => ['label' => '投稿管理', 'route' => 'admin.posts.index'],
        'users' => ['label' => 'ユーザー管理', 'route' => 'admin.users.index'],
        'categories' => ['label' => 'カテゴリ管理', 'route' => 'admin.categories.index'],
        'reports' => ['label' => '通報管理', 'route' => 'admin.reports.index'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="mq-admin">
    <header class="mq-admin-header">
        <div class="mq-admin-header__brand">まなびQ 管理</div>

        <nav class="mq-admin-nav" aria-label="管理メニュー">
            @foreach ($menu as $key => $item)
                <a href="{{ Route::has($item['route']) ? route($item['route']) : '#' }}"
                   @if ($active === $key) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <div class="mq-admin-header__user">
            @auth
                <span>{{ auth()->user()->name }} さん</span>
            @endauth
            <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                @csrf
                <button type="submit" class="mq-admin-logout">ログアウト</button>
            </form>
        </div>
    </header>

    <main class="mq-admin-main">
        <h1 class="mq-admin-title">{{ $title }}</h1>
        <x-flash class="mb-3" />
        {{ $slot }}
    </main>
</body>
</html>
