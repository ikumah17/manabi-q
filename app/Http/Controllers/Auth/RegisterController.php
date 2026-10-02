<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * G2 新規会員登録 → G3 会員登録完了（自動ログインせず G1 へ戻す）
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        User::create($request->safe()->only(['name', 'email', 'password']));

        return redirect()->route('register.complete')->with('registered', true);
    }

    /**
     * 登録直後のリダイレクトでのみ表示する（直接開いた場合は G1 へ）
     */
    public function complete(): View|RedirectResponse
    {
        if (! session('registered')) {
            return redirect()->route('login');
        }

        return view('auth.register-complete');
    }
}
