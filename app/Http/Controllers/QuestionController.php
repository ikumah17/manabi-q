<?php

namespace App\Http\Controllers;

use App\Actions\GenerateAiAnswer;
use App\Http\Requests\StoreQuestionRequest;
use App\Http\Requests\UpdateQuestionRequest;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * G5 質問投稿・G6 質問投稿完了・G7 質問詳細・G19 質問編集・G21 質問の削除
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

        // ⑩ いいねの件数（likes_count）と、ログインユーザーがいいね済みか（liked_by_me_count）
        $userId = $request->user()->id;
        $likeCounts = ['likes', 'likes as liked_by_me_count' => fn ($likes) => $likes->where('user_id', $userId)];

        $question->load(['user', 'category', 'aiAnswer'])->loadCount($likeCounts);

        // 公開中の回答を投稿日時順に。ベストアンサーは別枠（④）で表示し、件数には含める
        $answers = $question->answers()
            ->published()
            ->with(['user', 'stamp'])
            ->withCount($likeCounts)
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

    /**
     * G19 質問編集（権限はルートの can:update で確認する）
     */
    public function edit(Question $question): View
    {
        return view('questions.edit', [
            'question' => $question,
            'categories' => Category::ordered()->get(),
        ]);
    }

    /**
     * G19 ⑥ 更新する：タイトル・カテゴリ・本文のみ。AI回答希望は変更せず、AI回答も作り直さない
     */
    public function update(UpdateQuestionRequest $request, Question $question): RedirectResponse
    {
        $question->update($request->safe()->only(['title', 'category_id', 'body']));

        return redirect()->route('questions.show', $question)->with('success', '質問を更新しました');
    }

    /**
     * G21 ④ 削除する（物理削除。外部キーの連動で回答・AI回答・通報・いいねも削除される。G14 の削除と同じ）
     */
    public function destroy(Question $question): RedirectResponse
    {
        $deleted = DB::transaction(function () use ($question) {
            $locked = Question::query()->whereKey($question->id)->lockForUpdate()->first();

            return $locked !== null && $locked->delete();
        });

        return $deleted
            ? redirect()->route('home')->with('success', '質問を削除しました')
            : self::missing();
    }

    /**
     * 既に削除された質問の削除を再送信した場合（404 にせず G4 へ）
     */
    public static function missing(): RedirectResponse
    {
        return redirect()->route('home')->with('error', '既に削除されています');
    }
}
