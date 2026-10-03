<?php

namespace App\Services\Ranking;

use App\Models\Stamp;
use Illuminate\Support\Collection;

/**
 * 回答者ランキングの1行（G4 ⑨・G10 ①②・G11 ①）
 */
final class RankingEntry
{
    /**
     * @param  Collection<int, Stamp>  $earnedStamps
     * @param  int  $ratingSum  評価の入ったベストアンサーの★の合計
     * @param  int  $ratedCount  評価の入ったベストアンサーの件数
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly int $rank,
        public readonly int $answerCount,
        public readonly int $bestAnswerCount,
        public readonly Collection $earnedStamps,
        public readonly int $ratingSum = 0,
        public readonly int $ratedCount = 0,
    ) {}

    /**
     * ベストアンサー評価の平均（小数第1位で四捨五入。評価が無ければ null）
     * DB の ROUND は DB ごとに丸め方が違うため使わず、合計と件数から PHP で計算する
     */
    public function averageRating(): ?float
    {
        return $this->ratedCount > 0 ? round($this->ratingSum / $this->ratedCount, 1) : null;
    }

    /**
     * G10 ①「★4.3」の数値部分（評価が無ければ「—」）
     */
    public function averageRatingText(): string
    {
        $average = $this->averageRating();

        return $average === null ? '—' : number_format($average, 1);
    }
}
