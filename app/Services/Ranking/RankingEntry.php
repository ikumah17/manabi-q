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
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly int $rank,
        public readonly int $answerCount,
        public readonly int $bestAnswerCount,
        public readonly Collection $earnedStamps,
    ) {}
}
