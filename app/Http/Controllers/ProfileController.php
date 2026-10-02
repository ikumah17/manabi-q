<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * G12 プロフィール編集
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->name = $request->validated('name');
        $user->email = $request->validated('email');

        $passwordChanged = filled($request->validated('new_password'));
        if ($passwordChanged) {
            $user->password = $request->validated('new_password');
        }

        $user->save();

        // パスワードを変えた場合はセッション ID を作り直す（ログインは保つ）
        if ($passwordChanged) {
            $request->session()->regenerate();
        }

        return redirect()->route('mypage')->with('success', 'プロフィールを更新しました');
    }
}
