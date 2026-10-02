<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * G4 ホーム（質問一覧）
 * Phase 3 では遷移先としての仮画面。Phase 4 で一覧・検索を実装する
 */
class HomeController extends Controller
{
    public function index(): View
    {
        return view('home');
    }
}
