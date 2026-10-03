<?php

namespace App\Services\Ranking;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Answer;
use App\Models\Stamp;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 回答者ランキングの集計（G4 ⑨ TOP5・G10・G11 ① で共通に使う）
 *
 * - 数える回答：回答と質問の両方が公開中のもの（Answer::countable()）
 * - 対象：回答が1件以上ある、利用中の一般ユーザー（管理者・利用停止は除く）
 * - 順位：回答数のみで決める（同数は同順位：1, 2, 2, 4）
 * - 並び順：回答数 → ベストアンサー数 → ユーザーID
 * - ベストアンサー評価の平均（G10 ①・G11 ①）：数える回答のうち評価の入ったベストアンサーの合計と件数から計算する（順位には使わない）
 */
class RankingService
{
    /**
     * @var Collection<int, Stamp>|null
     */
    private ?Collection $stamps = null;

    /**
     * スタンプ獲得の判定に使う回答数（G8・G11 ②）
     */
    public function answerCount(User $user): int
    {
        return Answer::countable()->where('answers.user_id', $user->id)->count();
    }

    /**
     * G10 ① ランキング一覧（20件ごと）
     *
     * @return LengthAwarePaginator<int, RankingEntry>
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return $this->ordered($this->rankedQuery())
            ->paginate($perPage)
            ->through(fn (object $row) => $this->toEntry($row));
    }

    /**
     * G4 ⑨ 回答者ランキング TOP5
     *
     * @return Collection<int, RankingEntry>
     */
    public function top(int $limit = 5): Collection
    {
        return $this->ordered($this->rankedQuery())
            ->limit($limit)
            ->get()
            ->map(fn (object $row) => $this->toEntry($row));
    }

    /**
     * G10 ② あなたの順位・G11 ① の順位（ランキング対象外なら null）
     */
    public function forUser(User $user): ?RankingEntry
    {
        $row = DB::query()
            ->fromSub($this->rankedQuery(), 'ranked')
            ->where('user_id', $user->id)
            ->first();

        return $row ? $this->toEntry($row) : null;
    }

    /**
     * 獲得済みスタンプ（必要回答数の少ない順）
     *
     * @return Collection<int, Stamp>
     */
    public function earnedStamps(int $answerCount): Collection
    {
        return $this->stamps()->filter(fn (Stamp $stamp) => $stamp->isEarnedWith($answerCount))->values();
    }

    /**
     * @return Collection<int, Stamp>
     */
    public function stamps(): Collection
    {
        return $this->stamps ??= Stamp::ordered()->get();
    }

    /**
     * 順位付きの集計（順位はページ分けや絞り込みの前に全体で計算する）
     */
    private function rankedQuery(): QueryBuilder
    {
        $answers = Answer::countable()->getQuery();

        $counts = DB::query()
            ->fromSub(
                (clone $answers)
                    ->select('answers.user_id')
                    ->selectRaw('COUNT(*) as answer_count')
                    ->selectRaw('SUM(CASE WHEN answers.is_best = 1 THEN 1 ELSE 0 END) as best_answer_count')
                    // ベストアンサー評価の平均用（評価の入ったベストアンサーだけ。平均は toEntry() 側で計算する）
                    ->selectRaw('SUM(CASE WHEN answers.is_best = 1 AND answers.rating IS NOT NULL THEN answers.rating ELSE 0 END) as rating_sum')
                    ->selectRaw('SUM(CASE WHEN answers.is_best = 1 AND answers.rating IS NOT NULL THEN 1 ELSE 0 END) as rated_count')
                    ->groupBy('answers.user_id'),
                'counts'
            )
            ->join('users', 'users.id', '=', 'counts.user_id')
            ->where('users.role', UserRole::User->value)
            ->where('users.status', UserStatus::Active->value)
            ->select('counts.user_id', 'users.name', 'counts.answer_count', 'counts.best_answer_count', 'counts.rating_sum', 'counts.rated_count');

        return DB::query()
            ->fromSub($counts, 'c')
            ->select('c.*')
            ->selectRaw('RANK() OVER (ORDER BY c.answer_count DESC) as rank_no');
    }

    private function ordered(QueryBuilder $query): QueryBuilder
    {
        return $query->orderByDesc('answer_count')
            ->orderByDesc('best_answer_count')
            ->orderBy('user_id');
    }

    private function toEntry(object $row): RankingEntry
    {
        $answerCount = (int) $row->answer_count;

        return new RankingEntry(
            userId: (int) $row->user_id,
            name: (string) $row->name,
            rank: (int) $row->rank_no,
            answerCount: $answerCount,
            bestAnswerCount: (int) $row->best_answer_count,
            earnedStamps: $this->earnedStamps($answerCount),
            ratingSum: (int) $row->rating_sum,
            ratedCount: (int) $row->rated_count,
        );
    }
}
