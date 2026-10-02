<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 一般ユーザー画面（G4〜G12・G17）は一般ユーザーのみ。管理者は G13 へ移動させる
 */
class EnsureUserIsGeneral
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return $next($request);
    }
}
