<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 利用停止（users.status = 0）になったユーザーは、次のアクセスで強制ログアウトして G1 へ戻す
 */
class EnsureUserIsActive
{
    public const MESSAGE = 'このアカウントは利用停止されています';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => self::MESSAGE]);
        }

        return $next($request);
    }
}
