<?php

namespace App\Http\Controllers;

use App\Actions\GenerateAiAnswer;
use App\Http\Requests\StoreQuestionRequest;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * G5 質問投稿・G6 質問投稿完了・G7 質問詳細
 */
class QuestionController extends Controller
{
    public function create(): View
    {
        return view('questions.create', [
            'categories' => Category::ordered()->get(),
        ]);
    }

    public function store(StoreQuestionRequest $request, GenerateAiAnswer $generateAiAnswer): RedirectResponse
    {
        $question = $request->user()->questions()->create([
            ...$request->safe()->only(['title', 'category_id', 'body']),
            'use_ai' => $request->boolean('use_ai'),
        ]);

        // 質問の保存を確定させてから AI 即時回答を生成する（失敗しても投稿は成功）
        $aiResult = $question->use_ai ? $generateAiAnswer->handle($question) : null;

        return redirect()->route('questions.complete', $question)
            ->with('ai_error', $aiResult?->message());
    }

    /**
     * G6：質問者本人のみ（本人以外は G7 へ）
     */
    public function complete(Request $request, Question $question): View|RedirectResponse
    {
        Gate::authorize('view', $question);

        if ($question->user_id !== $request->user()->id) {
            return redirect()->route('questions.show', $question);
        }

        $question->load(['category', 'aiAnswer']);

        return view('questions.complete', ['question' => $question]);
    }

    public function show(Request $request, Question $question): View
    {
        Gate::authorize('view', $question);

        $question->load(['user', 'category', 'aiAnswer']);

        // 公開中の回答を投稿日時順に。ベストアンサーは別枠（④）で表示し、件数には含める
        $answers = $question->answers()
            ->published()
            ->with(['user', 'stamp'])
            ->oldest()
            ->orderBy('id')
            ->get()
            ->each(fn ($answer) => $answer->setRelation('question', $question)); // 権限の判定で質問を再取得しない

        return view('questions.show', [
            'question' => $question,
            'answerCount' => $answers->count(),
            'bestAnswer' => $answers->firstWhere('is_best', true),
            'answers' => $answers->reject(fn ($answer) => $answer->is_best)->values(),
            'isOwner' => $question->user_id === $request->user()->id,
        ]);
    }
}
