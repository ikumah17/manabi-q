{{-- G3 会員登録完了（自動ログインせず G1 へ戻す） --}}
<x-layouts.mobile title="会員登録完了">
    <div class="text-center mq-complete">
        <div class="mq-complete__icon" aria-hidden="true">✓</div>

        {{-- ① 完了メッセージ --}}
        <h1 class="mq-complete__title">会員登録が完了しました</h1>
        <p class="mq-complete__text">登録したメールアドレスとパスワードで<br>ログインしてください</p>
    </div>

    {{-- ② ログイン画面へ --}}
    <a href="{{ route('login') }}" class="btn btn-primary w-100">ログイン画面へ</a>
</x-layouts.mobile>
