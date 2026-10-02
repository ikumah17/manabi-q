<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardStats;
use Illuminate\View\View;

/**
 * G13 管理者ダッシュボード
 */
class DashboardController extends Controller
{
    public function index(DashboardStats $stats): View
    {
        return view('admin.dashboard', [
            'summary' => $stats->summary(),
            'monthly' => $stats->monthlyQuestions(7),
            'byCategory' => $stats->questionsByCategory(),
            'recentPosts' => $stats->recentPosts(5),
        ]);
    }
}
