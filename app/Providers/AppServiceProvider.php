<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
    }
}
