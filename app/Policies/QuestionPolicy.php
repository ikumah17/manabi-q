<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuestionPolicy
{
    /**
     * G7 質問詳細：非表示の質問は本人を含め見せない（404）
     */
    public function view(User $user, Question $question): Response
    {
        return $question->isPublished()
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * G8 回答投稿：公開中の質問なら回答できる（自分の質問・解決済みの質問にも回答可）
     */
    public function answer(User $user, Question $question): Response
    {
        return $this->view($user, $question);
    }

    /**
     * G7 ⑨・G17 質問の通報：非表示の質問は 404、自分の質問は 403
     */
    public function report(User $user, Question $question): Response
    {
        if (! $question->isPublished()) {
            return Response::denyAsNotFound();
        }

        return $question->user_id === $user->id
            ? Response::deny()
            : Response::allow();
    }

    /**
     * G7 ⑪・G19 質問の編集：質問者本人のみ（403）。非表示の質問は 404。解決済みの質問も編集できる
     */
    public function update(User $user, Question $question): Response
    {
        if (! $question->isPublished()) {
            return Response::denyAsNotFound();
        }

        return $question->user_id === $user->id
            ? Response::allow()
            : Response::deny();
    }

    /**
     * G7 ⑫・G21 質問の削除：編集と同じ条件（解決済み・回答が付いた質問も削除できる）
     */
    public function delete(User $user, Question $question): Response
    {
        return $this->update($user, $question);
    }

    /**
     * G6・G7 ③ AI回答の再試行：質問者本人・AI回答を希望・まだAI回答が無い場合のみ
     * （非表示の質問は view() で 404 にする）
     */
    public function retryAiAnswer(User $user, Question $question): bool
    {
        return $question->user_id === $user->id
            && $question->use_ai
            && ! $question->aiAnswer()->exists();
    }

    /**
     * G7 ⑥・G9 ベストアンサーに選ぶ：質問者本人・未解決・公開中の他人の回答のみ
     */
    public function chooseBest(User $user, Question $question, Answer $answer): bool
    {
        return $question->isPublished()
            && $question->user_id === $user->id
            && ! $question->is_resolved
            && $answer->question_id === $question->id
            && $answer->isPublished()
            && ! $answer->is_best
            && $answer->user_id !== $user->id;
    }
}
