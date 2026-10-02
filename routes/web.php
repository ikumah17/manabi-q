<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// トップ：未ログイン→G1、一般→G4、管理者→G13
Route::get('/', function (Request $request) {
    return $request->user()
        ? redirect($request->user()->homeUrl())
        : redirect()->route('login');
});

// 未ログインのみ（G1〜G3）
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/register/complete', [RegisterController::class, 'complete'])->name('register.complete');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // 一般ユーザー画面（管理者は G13 へ移動）
    Route::middleware('general')->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('home');
    });

    // 管理者画面（一般ユーザーは 403）
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
});

// 開発用：共通レイアウト・部品の表示確認（ローカル環境のみ。Phase 9 で削除）
if (app()->environment('local')) {
    Route::view('/_dev/mobile', 'dev.mobile-preview');
    Route::view('/_dev/admin', 'dev.admin-preview');
}
