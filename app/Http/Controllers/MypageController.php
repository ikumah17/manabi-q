<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Stamp;
use App\Services\Ranking\RankingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * G11 マイページ
 */
class MypageController extends Controller
{
    public function index(Request $request, RankingService $ranking): View
    {
        $user = $request->user();
        $tab = $request->query('tab') === 'answers' ? 'answers' : 'questions';

        $entry = $ranking->forUser($user);
        $answerCount = $ranking->answerCount($user);

        // ④ 投稿一覧（10件ごと。非表示の投稿は出さない）
        $list = $tab === 'answers'
            ? Answer::countable()
                ->where('answers.user_id', $user->id)
                ->with(['question' => fn ($query) => $query->forList()])
                ->latest()
                ->orderByDesc('id')
                ->paginate(10)
            : Question::published()
                ->where('user_id', $user->id)
                ->forList()
                ->latest()
                ->orderByDesc('id')
                ->paginate(10);

        return view('mypage.index', [
            'user' => $user,
            'tab' => $tab,
            'list' => $list->onEachSide(1)->withQueryString(),
            'questionCount' => Question::published()->where('user_id', $user->id)->count(),
            'answerCount' => $answerCount,
            'bestAnswerCount' => $entry?->bestAnswerCount ?? 0,
            'rank' => $entry?->rank,
            // ② 獲得スタンプ（未獲得は残りの回答数）
            'stamps' => $ranking->stamps()->map(fn (Stamp $stamp) => [
                'stamp' => $stamp,
                'earned' => $stamp->isEarnedWith($answerCount),
                'remaining' => max(0, $stamp->required_count - $answerCount),
            ]),
        ]);
    }
}
