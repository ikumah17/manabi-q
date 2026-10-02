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
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background: #f5f7fc;
            color: #212633;
            font-family: system-ui, -apple-system, "Hiragino Kaku Gothic ProN", "Hiragino Sans", Meiryo, sans-serif;
            line-height: 1.7;
        }
        .mq-error {
            width: 100%;
            max-width: 420px;
            padding: 32px 24px;
            background: #fff;
            border: 1px solid #d9dee8;
            border-radius: 12px;
            text-align: center;
        }
        .mq-error__brand { margin: 0 0 16px; color: #2e70d9; font-size: 18px; font-weight: 700; }
        .mq-error__code { margin: 0; color: #6b7587; font-size: 14px; letter-spacing: .08em; }
        .mq-error__heading { margin: 4px 0 12px; font-size: 20px; font-weight: 700; overflow-wrap: anywhere; }
        .mq-error__message { margin: 0 0 24px; color: #212633; font-size: 15px; overflow-wrap: anywhere; }
        .mq-error__button {
            display: block;
            padding: 12px 16px;
            background: #2e70d9;
            border-radius: 8px;
            color: #fff;
            font-weight: 700;
            text-decoration: none;
        }
        .mq-error__button:hover, .mq-error__button:focus-visible { background: #255cb3; }
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
