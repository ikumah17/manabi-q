<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnswerRequest;
use App\Http\Requests\UpdateAnswerRequest;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Stamp;
use App\Services\Ranking\RankingService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * G8 回答投稿・G20 回答編集・G21 回答の削除
 */
class AnswerController extends Controller
{
    public const MISSING = '既に削除されています';

    public const BEST_LOCKED = 'ベストアンサーに決定済みの回答は編集・削除できません';

    public function create(Request $request, Question $question, RankingService $ranking): View
    {
        Gate::authorize('answer', $question);

        return view('answers.create', [
            'question' => $question,
            'stamps' => Stamp::ordered()->get(),
            'answerCount' => $ranking->answerCount($request->user()),
        ]);
    }

    public function store(StoreAnswerRequest $request, Question $question): RedirectResponse
    {
        Gate::authorize('answer', $question);

        $question->answers()->create([
            'user_id' => $request->user()->id,
            'stamp_id' => $request->validated('stamp_id'),
            'body' => $request->validated('body'),
        ]);

        return redirect()->route('questions.show', $question)->with('success', '回答を投稿しました');
    }

    /**
     * G20 回答編集（権限はルートの can:update で確認する）
     */
    public function edit(Question $question, Answer $answer): View
    {
        return view('answers.edit', [
            'question' => $question,
            'answer' => $answer->load('stamp'),
        ]);
    }

    /**
     * G20 ⑥ 更新する：本文のみ（添付スタンプは変更しない）
     */
    public function update(UpdateAnswerRequest $request, Question $question, Answer $answer): RedirectResponse
    {
        $error = $this->whileEditable($question, $answer, function (Answer $locked) use ($request) {
            $locked->body = $request->validated('body');
            $locked->save();
        });

        return $error === null
            ? redirect()->to(route('questions.show', $question).'#answer-'.$answer->id)->with('success', '回答を更新しました')
            : redirect()->route('questions.show', $question)->with('error', $error);
    }

    /**
     * G21 ④ 削除する（物理削除。外部キーの連動でその通報・いいねも削除される）
     */
    public function destroy(Question $question, Answer $answer): RedirectResponse
    {
        $error = $this->whileEditable($question, $answer, fn (Answer $locked) => $locked->delete());

        return redirect()->route('questions.show', $question)
            ->with($error === null ? 'success' : 'error', $error ?? '回答を削除しました');
    }

    /**
     * 既に削除された回答の削除を再送信した場合（404 にせず、質問が残っていれば G7、無ければ G4 へ）
     */
    public static function missing(Request $request): RedirectResponse
    {
        // 別の質問の回答を指定した URL（回答は存在する）は 404 のまま
        abort_if(Answer::query()->whereKey($request->route()->originalParameter('answer'))->exists(), 404);

        $question = Question::published()->find($request->route()->originalParameter('question'));

        return ($question ? redirect()->route('questions.show', $question) : redirect()->route('home'))
            ->with('error', self::MISSING);
    }

    /**
     * 質問 → 回答の順に行をロックし（ベストアンサー決定と同じ順）、まだ編集できる状態なら処理する。
     * 権限の確認のあとにベストアンサーに決まった・削除された場合は、その理由を返す
     *
     * @param  Closure(Answer): mixed  $callback
     * @return string|null エラーメッセージ（処理できた場合 null）
     */
    private function whileEditable(Question $question, Answer $answer, Closure $callback): ?string
    {
        return DB::transaction(function () use ($question, $answer, $callback) {
            Question::query()->whereKey($question->id)->lockForUpdate()->first();
            $locked = Answer::query()->whereKey($answer->id)->lockForUpdate()->first();

            if ($locked === null) {
                return self::MISSING;
            }

            if ($locked->is_best) {
                return self::BEST_LOCKED;
            }

            $callback($locked);

            return null;
        });
    }
}
