<?php

namespace Tests\Feature\Ranking;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Stamp;
use App\Models\User;
use App\Services\Ranking\RankingEntry;
use App\Services\Ranking\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 回答者ランキングの集計（G4・G10・G11 共通）
 */
class RankingServiceTest extends TestCase
{
    use RefreshDatabase;

    private RankingService $ranking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ranking = app(RankingService::class);
        foreach ([10, 50, 100] as $count) {
            Stamp::factory()->requiring($count)->create();
        }
    }

    private function answers(User $user, int $count, array $state = []): void
    {
        Answer::factory()->for($user)->count($count)->create($state);
    }

    /**
     * @param  iterable<RankingEntry>  $entries
     * @return list<array{string, int, int, int}>
     */
    private function rows(iterable $entries): array
    {
        return collect($entries)->map(fn (RankingEntry $e) => [$e->name, $e->rank, $e->answerCount, $e->bestAnswerCount])->values()->all();
    }

    public function test_order_and_competition_ranking_with_ties(): void
    {
        $a = User::factory()->create(['name' => 'A']);
        $b = User::factory()->create(['name' => 'B']);
        $c = User::factory()->create(['name' => 'C']);
        $d = User::factory()->create(['name' => 'D']);
        $this->answers($a, 5);
        $this->answers($b, 3);
        $this->answers($c, 3);
        $this->answers($d, 1);
        Answer::factory()->for($c)->best()->create(); // C は 4件（うちBA1）
        Answer::factory()->for($b)->best()->create(); // B も 4件（うちBA1）
        Answer::factory()->for($b)->best()->create(); // B は 5件（うちBA2）→ A と同数

        // 回答数 5,5,4,1 → 順位 1,1,3,4。同数は BA数 → ID の順に並べる
        $this->assertSame([
            ['B', 1, 5, 2],
            ['A', 1, 5, 0],
            ['C', 3, 4, 1],
            ['D', 4, 1, 0],
        ], $this->rows($this->ranking->paginate()->items()));
    }

    public function test_answers_on_hidden_questions_and_hidden_answers_are_not_counted(): void
    {
        $user = User::factory()->create(['name' => 'U']);
        $this->answers($user, 2);
        $this->answers($user, 3, ['status' => 0]);                                      // 非表示の回答
        Answer::factory()->for($user)->for(Question::factory()->hidden())->count(4)->create(); // 非表示の質問への回答
        Answer::factory()->for($user)->for(Question::factory()->hidden())->best()->create();

        $entry = $this->ranking->forUser($user);

        $this->assertSame(2, $entry->answerCount);
        $this->assertSame(0, $entry->bestAnswerCount);
        $this->assertSame(2, $this->ranking->answerCount($user));
    }

    public function test_admins_suspended_users_and_users_without_answers_are_excluded(): void
    {
        $this->answers(User::factory()->admin()->create(['name' => '管理者']), 9);
        $this->answers(User::factory()->suspended()->create(['name' => '停止中']), 8);
        $noAnswers = User::factory()->create(['name' => '未回答']);
        $this->answers(User::factory()->create(['name' => '一般']), 1);

        $this->assertSame([['一般', 1, 1, 0]], $this->rows($this->ranking->paginate()->items()));
        $this->assertNull($this->ranking->forUser($noAnswers));
        $this->assertSame(0, $this->ranking->answerCount($noAnswers));
    }

    public function test_rank_is_computed_over_all_users_not_per_page(): void
    {
        foreach (range(1, 23) as $i) {
            $this->answers(User::factory()->create(['name' => sprintf('U%02d', $i)]), 30 - $i);
        }

        $first = $this->ranking->paginate(20);
        $this->assertSame(23, $first->total());
        $this->assertCount(20, $first->items());

        // 2ページ目（ページ番号はリクエストの page から読む）
        request()->query->set('page', 2);
        $second = $this->ranking->paginate(20);

        $this->assertSame([21, 22, 23], array_map(fn (RankingEntry $e) => $e->rank, $second->items()));
    }

    public function test_top_returns_first_five_in_ranking_order(): void
    {
        foreach (range(1, 7) as $i) {
            $this->answers(User::factory()->create(['name' => "U{$i}"]), 10 - $i);
        }

        $top = $this->ranking->top();

        $this->assertCount(5, $top);
        $this->assertSame(['U1', 'U2', 'U3', 'U4', 'U5'], $top->map->name->all());
        $this->assertSame([1, 2, 3, 4, 5], $top->map->rank->all());
    }

    public function test_for_user_returns_same_rank_as_list(): void
    {
        $users = collect(range(1, 4))->map(fn ($i) => User::factory()->create(['name' => "U{$i}"]));
        $this->answers($users[0], 4);
        $this->answers($users[1], 2);
        $this->answers($users[2], 2);
        $this->answers($users[3], 1);

        foreach ($this->ranking->paginate()->items() as $entry) {
            $mine = $this->ranking->forUser(User::find($entry->userId));
            $this->assertEquals($entry, $mine);
        }
        $this->assertSame(2, $this->ranking->forUser($users[2])->rank);
    }

    public function test_earned_stamps_by_answer_count_boundaries(): void
    {
        $this->assertSame([], $this->ranking->earnedStamps(9)->pluck('required_count')->all());
        $this->assertSame([10], $this->ranking->earnedStamps(10)->pluck('required_count')->all());
        $this->assertSame([10], $this->ranking->earnedStamps(49)->pluck('required_count')->all());
        $this->assertSame([10, 50], $this->ranking->earnedStamps(50)->pluck('required_count')->all());
        $this->assertSame([10, 50, 100], $this->ranking->earnedStamps(100)->pluck('required_count')->all());

        $user = User::factory()->create();
        $this->answers($user, 10);
        $this->assertSame([10], $this->ranking->forUser($user)->earnedStamps->pluck('required_count')->all());
    }
}
