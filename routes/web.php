<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AiAnswerController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BestAnswerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReportController;
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
        // G4 ホーム
        Route::get('/home', [HomeController::class, 'index'])->name('home');

        // G5 質問投稿・G6 投稿完了・G7 質問詳細
        Route::get('/questions/create', [QuestionController::class, 'create'])->name('questions.create');
        Route::post('/questions', [QuestionController::class, 'store'])->name('questions.store');
        Route::get('/questions/{question}', [QuestionController::class, 'show'])->name('questions.show');
        Route::get('/questions/{question}/complete', [QuestionController::class, 'complete'])->name('questions.complete');

        // G6・G7 ③ AI回答の再試行（質問者本人のみ）
        Route::post('/questions/{question}/ai-answer', [AiAnswerController::class, 'store'])->name('questions.ai-answer.retry');

        // G19 質問編集・G21 質問の削除（本人のみ 403、非表示の質問は 404。削除済みの再送信は G4 へ）
        Route::get('/questions/{question}/edit', [QuestionController::class, 'edit'])
            ->can('update', 'question')
            ->name('questions.edit');
        Route::put('/questions/{question}', [QuestionController::class, 'update'])
            ->can('update', 'question')
            ->name('questions.update');
        Route::delete('/questions/{question}', [QuestionController::class, 'destroy'])
            ->can('delete', 'question')
            ->missing(fn () => QuestionController::missing())
            ->name('questions.destroy');

        // G8 回答投稿
        Route::get('/questions/{question}/answers/create', [AnswerController::class, 'create'])->name('answers.create');
        Route::post('/questions/{question}/answers', [AnswerController::class, 'store'])->name('answers.store');

        // G20 回答編集・G21 回答の削除（本人のみ 403、ベストアンサーは 403、非表示は 404。削除済みの再送信は G7／G4 へ）
        Route::get('/questions/{question}/answers/{answer}/edit', [AnswerController::class, 'edit'])
            ->scopeBindings()
            ->can('update', 'answer')
            ->name('answers.edit');
        Route::put('/questions/{question}/answers/{answer}', [AnswerController::class, 'update'])
            ->scopeBindings()
            ->can('update', 'answer')
            ->name('answers.update');
        Route::delete('/questions/{question}/answers/{answer}', [AnswerController::class, 'destroy'])
            ->scopeBindings()
            ->can('delete', 'answer')
            ->missing(fn (Request $request) => AnswerController::missing($request))
            ->name('answers.destroy');

        // G17 通報（自分の投稿は 403、非表示の投稿は 404）
        Route::post('/questions/{question}/reports', [ReportController::class, 'storeForQuestion'])
            ->can('report', 'question')
            ->name('reports.question');
        Route::post('/questions/{question}/answers/{answer}/reports', [ReportController::class, 'storeForAnswer'])
            ->scopeBindings()
            ->can('report', 'answer')
            ->name('reports.answer');

        // G7 ⑩ いいね（POST で付ける・DELETE で取り消す。自分の投稿は 403、非表示の投稿は 404）
        Route::post('/questions/{question}/likes', [LikeController::class, 'storeForQuestion'])
            ->can('like', 'question')
            ->name('likes.question.store');
        Route::delete('/questions/{question}/likes', [LikeController::class, 'destroyForQuestion'])
            ->can('like', 'question')
            ->name('likes.question.destroy');
        Route::post('/questions/{question}/answers/{answer}/likes', [LikeController::class, 'storeForAnswer'])
            ->scopeBindings()
            ->can('like', 'answer')
            ->name('likes.answer.store');
        Route::delete('/questions/{question}/answers/{answer}/likes', [LikeController::class, 'destroyForAnswer'])
            ->scopeBindings()
            ->can('like', 'answer')
            ->name('likes.answer.destroy');

        // G10 回答者ランキング
        Route::get('/ranking', [RankingController::class, 'index'])->name('ranking');

        // G11 マイページ
        Route::get('/mypage', [MypageController::class, 'index'])->name('mypage');

        // G12 プロフィール編集
        Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/mypage/profile', [ProfileController::class, 'update'])->name('profile.update');

        // G9 ベストアンサー評価（回答はその質問のものに限る）
        Route::post('/questions/{question}/answers/{answer}/best', [BestAnswerController::class, 'store'])
            ->scopeBindings()
            ->name('answers.best');
    });

    // 管理者画面（一般ユーザーは 403）
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // G14 投稿管理
        Route::get('/posts', [AdminPostController::class, 'index'])->name('posts.index');
        Route::controller(AdminPostController::class)->group(function () {
            $missing = fn () => AdminPostController::missing();
            Route::post('/posts/questions/{question}/hide', 'hideQuestion')->name('posts.questions.hide')->missing($missing);
            Route::post('/posts/questions/{question}/publish', 'publishQuestion')->name('posts.questions.publish')->missing($missing);
            Route::delete('/posts/questions/{question}', 'destroyQuestion')->name('posts.questions.destroy')->missing($missing);
            Route::post('/posts/answers/{answer}/hide', 'hideAnswer')->name('posts.answers.hide')->missing($missing);
            Route::post('/posts/answers/{answer}/publish', 'publishAnswer')->name('posts.answers.publish')->missing($missing);
            Route::delete('/posts/answers/{answer}', 'destroyAnswer')->name('posts.answers.destroy')->missing($missing);
        });

        // G15 ユーザー管理（削除は無し）
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('users.suspend');
        Route::post('/users/{user}/resume', [AdminUserController::class, 'resume'])->name('users.resume');

        // G16 カテゴリ管理
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // G18 通報管理
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/{report}/hide-target', [AdminReportController::class, 'hideTarget'])->name('reports.hide-target');
        Route::post('/reports/{report}/resolve', [AdminReportController::class, 'resolve'])->name('reports.resolve');
    });
});
