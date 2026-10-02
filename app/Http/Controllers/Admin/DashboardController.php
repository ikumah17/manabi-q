<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * G13 管理者ダッシュボード
 * Phase 3 では遷移先としての仮画面。Phase 8 で集計・グラフを実装する
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard');
    }
}
