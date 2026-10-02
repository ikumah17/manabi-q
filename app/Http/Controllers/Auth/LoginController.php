<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * G1 ログイン／ログアウト
 */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        Auth::login($user);
        $request->session()->regenerate();

        // ログイン前に開こうとしたページ（同じサイト内のみ）へ。無ければ 一般ユーザー→G4、管理者→G13
        $intended = $request->session()->pull('url.intended');

        return redirect($this->isInternalUrl($request, $intended) ? $intended : $user->homeUrl());
    }

    /**
     * 戻り先が同じサイト（スキーム・ホスト・ポートが一致）の URL か。外部サイトへは移さない
     */
    private function isInternalUrl(Request $request, mixed $url): bool
    {
        if (! is_string($url) || $url === '') {
            return false;
        }

        $base = $request->getSchemeAndHttpHost();

        return $url === $base || str_starts_with($url, $base.'/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
