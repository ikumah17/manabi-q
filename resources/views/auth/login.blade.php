{{-- G1 ログイン --}}
<x-layouts.mobile title="ログイン">
    @php($failed = $errors->any())
    <div class="mq-auth">
        <h1 class="mq-page-title">ログイン</h1>
        <p class="mq-page-lead">まなびQ にログインして質問・回答に参加しよう</p>

        <form method="POST" action="{{ route('login') }}" novalidate>
            @csrf

            {{-- ① メールアドレス --}}
            <div class="mb-4">
                <label for="email" class="form-label">メールアドレス</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" @class(['form-control', 'is-invalid' => $failed])
                       maxlength="255" autocomplete="username" inputmode="email" placeholder="example@school.ac.jp" required autofocus
                       @if ($failed) aria-invalid="true" @endif>
            </div>

            {{-- ② パスワード --}}
            <div class="mb-4">
                <label for="password" class="form-label">パスワード</label>
                <input type="password" id="password" name="password" @class(['form-control', 'is-invalid' => $failed])
                       maxlength="64" autocomplete="current-password" required
                       @if ($failed) aria-invalid="true" @endif>
            </div>

            {{-- ③ エラーメッセージ --}}
            <x-form-errors class="mb-4" />

            {{-- ④ ログイン --}}
            <button type="submit" class="btn btn-primary w-100 mq-btn-tall">ログイン</button>
        </form>

        {{-- ⑤ 新規会員登録 --}}
        <p class="text-center text-body-secondary small mt-4 mb-0">
            アカウントをお持ちでない方は <a href="{{ route('register') }}">新規会員登録</a>
        </p>
    </div>
</x-layouts.mobile>
