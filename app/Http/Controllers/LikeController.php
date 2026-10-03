<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\Like;
use App\Models\Question;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * G7 ⑩ いいね（POST で付ける・DELETE で取り消す。二重に送っても結果は同じ。
 * 権限はルートの can:like で確認する：自分の投稿は 403、非表示は 404）
 */
class LikeController extends Controller
{
    public function storeForQuestion(Request $request, Question $question): RedirectResponse
    {
        $this->like(['user_id' => $request->user()->id, 'question_id' => $question->id]);

        return redirect()->route('questions.show', $question);
    }

    public function destroyForQuestion(Request $request, Question $question): RedirectResponse
    {
        Like::query()->where('user_id', $request->user()->id)->where('question_id', $question->id)->delete();

        return redirect()->route('questions.show', $question);
    }

    public function storeForAnswer(Request $request, Question $question, Answer $answer): RedirectResponse
    {
        $this->like(['user_id' => $request->user()->id, 'answer_id' => $answer->id]);

        return $this->backToAnswer($question, $answer);
    }

    public function destroyForAnswer(Request $request, Question $question, Answer $answer): RedirectResponse
    {
        Like::query()->where('user_id', $request->user()->id)->where('answer_id', $answer->id)->delete();

        return $this->backToAnswer($question, $answer);
    }

    /**
     * 既にいいね済みなら何もしない。同時に2回送られてユニーク制約に当たった場合も成功として扱う
     *
     * @param  array{user_id: int, question_id?: int, answer_id?: int}  $attributes
     */
    private function like(array $attributes): void
    {
        if (Like::query()->where($attributes)->exists()) {
            return;
        }

        try {
            Like::query()->create($attributes);
        } catch (UniqueConstraintViolationException) {
            // もう一方のリクエストが先に登録した（結果は「いいね済み」で同じ）
        }
    }

    private function backToAnswer(Question $question, Answer $answer): RedirectResponse
    {
        return redirect()->to(route('questions.show', $question).'#answer-'.$answer->id);
    }
}
