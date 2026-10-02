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
        if (! $answer->isPublished() || ! $answer->question->isPublished()) {
            return Response::denyAsNotFound();
        }

        return $answer->user_id === $user->id
            ? Response::deny()
            : Response::allow();
    }
}
