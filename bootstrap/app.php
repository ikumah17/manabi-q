<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsGeneral;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // 利用停止ユーザーは次のアクセスで強制ログアウト
        $middleware->web(append: [EnsureUserIsActive::class]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'general' => EnsureUserIsGeneral::class,
        ]);

        // 未ログイン→G1、ログイン済みで G1〜G3 を開いた場合→権限ごとのトップ
        $middleware->redirectTo(
            guests: fn () => route('login'),
            users: fn (Request $request) => $request->user()->homeUrl(),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
