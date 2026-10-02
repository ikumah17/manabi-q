{{--
    処理結果のメッセージ（リダイレクト後に1回だけ表示）
    session('success') : 完了メッセージ（例：G17 通報後の「通報を受け付けました」）
    session('error')   : 処理できなかった理由
--}}
@if (session('success'))
    <div {{ $attributes->class('mq-flash mq-flash--success') }} role="status">✓ {{ session('success') }}</div>
@endif
@if (session('error'))
    <div {{ $attributes->class('mq-error') }} role="alert">{{ session('error') }}</div>
@endif
