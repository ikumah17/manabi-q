<?php

namespace App\Providers;

use App\Support\Text;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ページネーションは設計画像の「‹ 1 2 3 ›」形式（管理画面は ->links('pagination.admin')）
        Paginator::defaultView('pagination.mobile');

        // 文字数基準の切り詰め（Str::limit は表示幅で数えるため、設計書の「〇文字」には Str::limitChars を使う）
        Str::macro('limitChars', fn (?string $value, int $length, string $end = Text::ELLIPSIS) => Text::truncate($value, $length, $end));
    }
}
