<?php

namespace App\Actions;

use App\Enums\PostStatus;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Support\Facades\DB;

/**
 * G9 ベストアンサー決定：answers.is_best=1・rating、questions.is_resolved=1 に更新する（決定後は変更不可）
 */
class ChooseBestAnswer
{
    /**
     * 質問の行をロックしてから状態を確かめ直すため、同時に決定しても2件目は失敗する
     *
     * @return bool 決定できた場合 true（既に解決済み・回答が対象外になっていた場合 false）
     */
    public function handle(Question $question, Answer $answer, int $rating): bool
    {
        return DB::transaction(function () use ($question, $answer, $rating) {
            $lockedQuestion = Question::whereKey($question->id)->lockForUpdate()->first();
            $lockedAnswer = Answer::whereKey($answer->id)->lockForUpdate()->first();

            if ($lockedQuestion === null
                || $lockedQuestion->is_resolved
                || $lockedQuestion->status !== PostStatus::Published
                || $lockedAnswer === null
                || $lockedAnswer->question_id !== $lockedQuestion->id
                || $lockedAnswer->status !== PostStatus::Published
                || $lockedAnswer->user_id === $lockedQuestion->user_id
            ) {
                return false;
            }

            $lockedAnswer->is_best = true;
            $lockedAnswer->rating = $rating;
            $lockedAnswer->save();

            $lockedQuestion->is_resolved = true;
            $lockedQuestion->save();

            return true;
        });
    }
}
