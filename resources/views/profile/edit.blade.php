{{-- G12 プロフィール編集 --}}
<x-layouts.mobile header="プロフィール編集" :back="route('mypage')">
    <form method="POST" action="{{ route('profile.update') }}" novalidate data-submit-once>
        @csrf
        @method('PUT')

        {{-- ① ユーザー名 --}}
        <div class="mb-4">
            <label for="name" class="form-label">ユーザー名（表示名）</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" @class(['form-control', 'is-invalid' => $errors->has('name')])
                   maxlength="20" autocomplete="nickname" required
                   @error('name') aria-invalid="true" @enderror>
        </div>

        {{-- ② メールアドレス --}}
        <div class="mb-4">
            <label for="email" class="form-label">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" @class(['form-control', 'is-invalid' => $errors->has('email')])
                   maxlength="255" autocomplete="email" inputmode="email" required
                   @error('email') aria-invalid="true" @enderror>
        </div>

        <p class="mq-form-note">パスワードを変更する場合のみ入力してください</p>

        {{-- ③ 現在のパスワード --}}
        <div class="mb-4">
            <label for="current_password" class="form-label">現在のパスワード</label>
            <input type="password" id="current_password" name="current_password" @class(['form-control', 'is-invalid' => $errors->has('current_password')])
                   maxlength="64" autocomplete="current-password"
                   @error('current_password') aria-invalid="true" @enderror>
        </div>

        {{-- ④ 新しいパスワード --}}
        <div class="mb-4">
            <label for="new_password" class="form-label">新しいパスワード</label>
            <input type="password" id="new_password" name="new_password" @class(['form-control', 'is-invalid' => $errors->has('new_password')])
                   maxlength="64" autocomplete="new-password" placeholder="8文字以上の半角英数字"
                   @error('new_password') aria-invalid="true" @enderror>
        </div>

        {{-- ⑤ エラーメッセージ --}}
        <x-form-errors class="mb-4" />

        {{-- ⑦ 更新する --}}
        <button type="submit" class="btn btn-primary w-100 mq-btn-tall">更新する</button>
    </form>
</x-layouts.mobile>
