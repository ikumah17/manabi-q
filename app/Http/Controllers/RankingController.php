<?php

namespace App\Http\Controllers;

use App\Services\Ranking\RankingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * G10 回答者ランキング
 */
class RankingController extends Controller
{
    public function index(Request $request, RankingService $ranking): View
    {
        return view('ranking.index', [
            'entries' => $ranking->paginate(20)->onEachSide(1),
            'mine' => $ranking->forUser($request->user()),
            'userId' => $request->user()->id,
        ]);
    }
}
