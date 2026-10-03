{{-- G2 新規会員登録 --}}
<x-layouts.mobile header="新規会員登録" :back="route('login')">
    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        {{-- ① ユーザー名 --}}
        <div class="mb-4">
            <label for="name" class="form-label">ユーザー名（表示名）</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" @class(['form-control', 'is-invalid' => $errors->has('name')])
                   maxlength="20" autocomplete="nickname" placeholder="例）まなび太郎　※20文字以内" required autofocus
                   @error('name') aria-invalid="true" @enderror>
        </div>

        {{-- ② メールアドレス --}}
        <div class="mb-4">
            <label for="email" class="form-label">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" @class(['form-control', 'is-invalid' => $errors->has('email')])
                   maxlength="255" autocomplete="email" inputmode="email" placeholder="example@school.ac.jp" required
                   @error('email') aria-invalid="true" @enderror>
        </div>

        {{-- ③ パスワード --}}
        <div class="mb-4">
            <label for="password" class="form-label">パスワード</label>
            <input type="password" id="password" name="password" @class(['form-control', 'is-invalid' => $errors->has('password')])
                   maxlength="64" autocomplete="new-password" placeholder="8文字以上の半角英数字" required
                   @error('password') aria-invalid="true" @enderror>
        </div>

        {{-- ④ パスワード（確認） --}}
        <div class="mb-4">
            <label for="password_confirmation" class="form-label">パスワード（確認）</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                   maxlength="64" autocomplete="new-password" placeholder="もう一度入力してください" required>
        </div>

        {{-- ⑤ エラーメッセージ --}}
        <x-form-errors class="mb-4" />

        {{-- ⑥ 登録する --}}
        <button type="submit" class="btn btn-primary w-100 mq-btn-tall">登録する</button>
    </form>

    {{-- ⑦ ログイン画面へ --}}
    <p class="text-center text-body-secondary small mt-4">
        登録済みの方は <a href="{{ route('login') }}">ログイン画面へ</a>
    </p>
</x-layouts.mobile>
