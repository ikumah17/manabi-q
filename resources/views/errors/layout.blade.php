{{--
    エラー画面の共通レイアウト（403 / 404 / 419 / 429 / 500 / 503 / 4xx / 5xx）
    - 障害時でも表示できるよう、@vite・ログイン状態・セッション・DB・名前付きルートに依存しない（CSS もこのファイル内に書く）
    - 例外メッセージは表示しない
    - セクション：code（ステータスコード）/ heading（見出し）/ message（本文）
--}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('heading') | まなびQ</title>
    <style>
        /* 色・書体はアプリのトークンと同じ値（_tokens.scss）。このファイルだけで完結させる */
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            background: #f5f5f7;
            color: #1d1d1f;
            font-family: -apple-system, BlinkMacSystemFont, "Hiragino Sans", "Hiragino Kaku Gothic ProN", "Yu Gothic UI", "Meiryo", "Noto Sans JP", sans-serif;
            line-height: 1.7;
        }
        .mq-error {
            width: 100%;
            max-width: 420px;
            padding: 32px 24px;
            background: #fff;
            border: 1px solid #e5e5ea;
            border-radius: 20px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
            text-align: center;
        }
        .mq-error__brand { margin: 0 0 20px; color: #1d1d1f; font-size: 17px; font-weight: 600; }
        .mq-error__code { margin: 0; color: #6e6e73; font-size: 13px; letter-spacing: .06em; }
        .mq-error__heading { margin: 4px 0 8px; font-size: 22px; font-weight: 600; overflow-wrap: anywhere; }
        .mq-error__message { margin: 0 0 28px; color: #6e6e73; font-size: 15px; overflow-wrap: anywhere; }
        .mq-error__button {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 16px;
            background: #0066cc;
            border-radius: 12px;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
        }
        .mq-error__button:hover { background: #0058b0; }
        .mq-error__button:focus-visible { outline: 0; box-shadow: 0 0 0 3px rgba(0, 122, 255, .25); }
    </style>
</head>
<body>
    <main class="mq-error">
        <p class="mq-error__brand">まなびQ</p>
        <p class="mq-error__code">エラー @yield('code')</p>
        <h1 class="mq-error__heading">@yield('heading')</h1>
        <p class="mq-error__message">@yield('message')</p>
        <a href="{{ url('/') }}" class="mq-error__button">トップへ戻る</a>
    </main>
</body>
</html>
