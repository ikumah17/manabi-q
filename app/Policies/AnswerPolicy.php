<?php

namespace App\Policies;

use App\Models\Answer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AnswerPolicy
{
    /**
     * G7 ⑨・G17 回答の通報：非表示の回答・非表示の質問への回答は 404、自分の回答は 403
     */
    public function report(User $user, Answer $answer): Response
    {
        if (! $this->isVisible($answer)) {
            return Response::denyAsNotFound();
        }

        return $answer->user_id === $user->id
            ? Response::deny()
            : Response::allow();
    }

    /**
     * G7 ⑪・G20 回答の編集：回答者本人のみ（403）。非表示の回答・非表示の質問への回答は 404。
     * ベストアンサーに決定済みの回答は編集できない（403）
     */
    public function update(User $user, Answer $answer): Response
    {
        if (! $this->isVisible($answer)) {
            return Response::denyAsNotFound();
        }

        if ($answer->user_id !== $user->id) {
            return Response::deny();
        }

        return $answer->is_best
            ? Response::deny('ベストアンサーに決定済みの回答は編集・削除できません')
            : Response::allow();
    }

    /**
     * G7 ⑫・G21 回答の削除：編集と同じ条件
     */
    public function delete(User $user, Answer $answer): Response
    {
        return $this->update($user, $answer);
    }

    private function isVisible(Answer $answer): bool
    {
        return $answer->isPublished() && $answer->question->isPublished();
    }
}
