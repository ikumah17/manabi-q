<?php

namespace App\Actions;

use App\Enums\PostStatus;
use App\Enums\ReportStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Report;
use Illuminate\Support\Facades\DB;

/**
 * G18 通報への対応（トランザクション内で通報の行をロックして、対応済みでないことを確かめ直す）
 */
class HandleReport
{
    /**
     * ⑤ 対象を非表示：対象の投稿を status=0 にし、その対象への未対応の通報をすべて対応済みにする
     * （設計書は「通報を対応済みにする」。同じ投稿への他の人の通報もまとめて片付ける）
     *
     * @return bool 対応した場合 true（既に対応済みだった場合 false）
     */
    public function hideTarget(Report $report): bool
    {
        return DB::transaction(function () use ($report) {
            $locked = Report::whereKey($report->id)->lockForUpdate()->first();

            if ($locked === null || $locked->status === ReportStatus::Resolved) {
                return false;
            }

            $target = $locked->answer_id !== null
                ? Answer::whereKey($locked->answer_id)->lockForUpdate()->first()
                : Question::whereKey($locked->question_id)->lockForUpdate()->first();

            if ($target !== null && $target->status !== PostStatus::Hidden) {
                $target->status = PostStatus::Hidden;
                $target->save();
            }

            Report::query()
                ->where('status', ReportStatus::Pending->value)
                ->when(
                    $locked->answer_id !== null,
                    fn ($query) => $query->where('answer_id', $locked->answer_id),
                    fn ($query) => $query->where('question_id', $locked->question_id)->whereNull('answer_id'),
                )
                ->update(['status' => ReportStatus::Resolved->value, 'updated_at' => now()]);

            return true;
        });
    }

    /**
     * ⑥ 対応済みにする：この通報だけを対応済みにする（対象の投稿はそのまま）
     *
     * @return bool 対応した場合 true（既に対応済みだった場合 false）
     */
    public function resolve(Report $report): bool
    {
        return DB::transaction(function () use ($report) {
            $locked = Report::whereKey($report->id)->lockForUpdate()->first();

            if ($locked === null || $locked->status === ReportStatus::Resolved) {
                return false;
            }

            $locked->status = ReportStatus::Resolved;
            $locked->save();

            return true;
        });
    }
}
