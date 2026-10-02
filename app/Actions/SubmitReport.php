<?php

namespace App\Actions;

use App\Enums\ReportReason;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * G17 通報の登録
 *
 * reports の UNIQUE(user_id, question_id, answer_id) は片方が必ず NULL のため重複を防げない。
 * そこで通報者のユーザー行をロックし、同じ人の通報を1件ずつ処理してから重複を確かめる。
 * 同じ人が同じ投稿を通報できるのは、対応状況にかかわらず1回まで。
 */
class SubmitReport
{
    /**
     * @return bool 登録した場合 true（既に通報済みの場合 false）
     */
    public function handle(User $reporter, Question|Answer $target, ReportReason $reason, ?string $detail): bool
    {
        return DB::transaction(function () use ($reporter, $target, $reason, $detail) {
            User::whereKey($reporter->id)->lockForUpdate()->first();

            if ($this->alreadyReported($reporter, $target)) {
                return false;
            }

            Report::create([
                'user_id' => $reporter->id,
                // 回答を通報した場合は question_id を NULL にする（設計書どおり）
                'question_id' => $target instanceof Question ? $target->id : null,
                'answer_id' => $target instanceof Answer ? $target->id : null,
                'reason' => $reason,
                'detail' => filled($detail) ? $detail : null,
            ]);

            return true;
        });
    }

    public function alreadyReported(User $reporter, Question|Answer $target): bool
    {
        return Report::where('user_id', $reporter->id)
            ->when(
                $target instanceof Question,
                fn ($query) => $query->where('question_id', $target->id)->whereNull('answer_id'),
                fn ($query) => $query->where('answer_id', $target->id)->whereNull('question_id'),
            )
            ->exists();
    }
}
