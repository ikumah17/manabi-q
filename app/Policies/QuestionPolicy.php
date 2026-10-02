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
