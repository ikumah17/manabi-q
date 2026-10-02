<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Question;
use App\Services\Ranking\RankingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * G4 ホーム（質問一覧）
 */
class HomeController extends Controller
{
    /**
     * 並び替え（G4 ⑤）の選択肢
     */
    public const SORTS = [
        'new' => '新着順',
        'unresolved' => '未解決のみ',
        'answers' => '回答の多い順',
    ];

    public function index(Request $request, RankingService $ranking): View
    {
        $categories = Category::ordered()->get();

        // 検索条件。不正な値は無視して初期値（すべて・新着順）として扱う
        $keyword = is_string($request->query('keyword')) ? mb_substr(trim($request->query('keyword')), 0, 50) : '';
        $categoryId = $categories->contains('id', $request->integer('category')) ? $request->integer('category') : null;
        $sort = array_key_exists((string) $request->query('sort'), self::SORTS) ? (string) $request->query('sort') : 'new';

        $questions = Question::published()
            ->forList()
            ->when($keyword !== '', fn ($query) => $query->search($keyword))
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->sorted($sort)
            ->paginate(10)
            ->onEachSide(1)
            ->withQueryString();

        return view('home', [
            'questions' => $questions,
            'categories' => $categories,
            'sorts' => self::SORTS,
            'keyword' => $keyword,
            'categoryId' => $categoryId,
            'sort' => $sort,
            'topRankers' => $ranking->top(5),
        ]);
    }
}
