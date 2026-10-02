<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 開発用：共通レイアウト・部品の表示確認（ローカル環境のみ）
if (app()->environment('local')) {
    Route::view('/_dev/mobile', 'dev.mobile-preview');
    Route::view('/_dev/admin', 'dev.admin-preview');
}
