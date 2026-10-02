<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 管理者画面（G13〜G16・G18）は管理者のみ。一般ユーザーは 403
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return $next($request);
    }
}
