<?php

namespace Tests\Feature\Ranking;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Stamp;
use App\Models\User;
use App\Services\Ranking\RankingEntry;
use App\Services\Ranking\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ベストアンサー評価の平均（G10 ①・G11 ①）
 */
class RatingAverageTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        foreach ([10, 50, 100] as $count) {
            Stamp::factory()->requiring($count)->create();
        }
        $this->me = User::factory()->create(['name' => '山田 太郎']);
    }

    /**
     * @param  list<int>  $ratings
     */
    private function bestAnswers(User $user, array $ratings): void
    {
        foreach ($ratings as $rating) {
            Answer::factory()->for($user)->best($rating)->create();
        }
    }

    private function entry(int $ratingSum, int $ratedCount): RankingEntry
    {
        return new RankingEntry(1, 'A', 1, 10, $ratedCount, collect(), $ratingSum, $ratedCount);
    }

    public function test_average_is_rounded_half_up_to_one_decimal_in_php(): void
    {
        $this->assertSame(4.3, $this->entry(17, 4)->averageRating()); // 4.25 → 4.3（境界値）
        $this->assertSame(4.8, $this->entry(19, 4)->averageRating()); // 4.75 → 4.8
        $this->assertSame(4.3, $this->entry(13, 3)->averageRating()); // 4.333… → 4.3
        $this->assertSame(4.7, $this->entry(14, 3)->averageRating()); // 4.666… → 4.7
        $this->assertSame(5.0, $this->entry(5, 1)->averageRating());  // 1件
        $this->assertNull($this->entry(0, 0)->averageRating());       // 0件

        $this->assertSame('4.3', $this->entry(17, 4)->averageRatingText());
        $this->assertSame('5.0', $this->entry(5, 1)->averageRatingText());
        $this->assertSame('—', $this->entry(0, 0)->averageRatingText());
    }

    public function test_service_counts_only_rated_best_answers_on_published_posts(): void
    {
        $this->bestAnswers($this->me, [4, 5]);
        Answer::factory()->for($this->me)->count(3)->create();                                   // ベストアンサーではない
        Answer::factory()->for($this->me)->best(1)->hidden()->create();                         // 非表示の回答
        Answer::factory()->for($this->me)->for(Question::factory()->hidden())->best(1)->create(); // 非表示の質問への回答
        // 評価の入っていないベストアンサー（アプリでは作られないが、DB では NULL を許すため確認する）
        $unrated = Answer::factory()->for($this->me)->best()->create();
        DB::table('answers')->where('id', $unrated->id)->update(['rating' => null]);

        $entry = app(RankingService::class)->forUser($this->me);

        $this->assertSame([9, 2, 4.5], [$entry->ratingSum, $entry->ratedCount, $entry->averageRating()]);
        $this->assertSame(3, $entry->bestAnswerCount); // ベストアンサー数は従来どおり（評価なしも含む）
    }

    public function test_mypage_shows_average_and_count(): void
    {
        $this->bestAnswers($this->me, [4, 4, 4, 5]); // 4.25 → 4.3

        $response = $this->actingAs($this->me)->get('/mypage')->assertOk();

        $response->assertSeeTextInOrder(['質問', '回答', 'BA', '順位', 'ベストアンサー評価の平均', '4.3・4件', 'プロフィール編集']);
        $response->assertSee('<span class="mq-stamp-chip__star" aria-hidden="true">★</span>4.3・4件', false);
        $this->assertSame([4.3, 4], [$response->viewData('averageRating'), $response->viewData('ratedCount')]);
    }

    public function test_mypage_shows_dash_without_rated_best_answers(): void
    {
        Answer::factory()->for($this->me)->count(2)->create();

        $response = $this->actingAs($this->me)->get('/mypage')->assertOk();

        $response->assertSeeTextInOrder(['ベストアンサー評価の平均', '—', 'プロフィール編集']);
        $response->assertDontSee('件</dd>', false);
        $this->assertSame([null, 0], [$response->viewData('averageRating'), $response->viewData('ratedCount')]);

        // 回答が無くランキング対象外のユーザーも「—」
        $this->actingAs(User::factory()->create())->get('/mypage')
            ->assertSeeTextInOrder(['ベストアンサー評価の平均', '—', 'プロフィール編集']);
    }

    public function test_ranking_list_shows_average_per_user(): void
    {
        $hanako = User::factory()->create(['name' => '情報 花子']);
        $this->bestAnswers($hanako, [5, 4]);
        Answer::factory()->for($hanako)->count(3)->create();
        $this->bestAnswers($this->me, [3]);
        Answer::factory()->for(User::factory()->create(['name' => '文学 三子']))->create();

        $response = $this->actingAs($this->me)->get('/ranking')->assertOk();

        $response->assertSeeTextInOrder([
            '情報 花子', 'ベストアンサー 2件', 'ベストアンサー評価の平均', '4.5',
            '山田 太郎', 'ベストアンサー 1件', 'ベストアンサー評価の平均', '3.0',
            '文学 三子', 'ベストアンサー 0件', 'ベストアンサー評価の平均', '—',
        ]);
        $this->assertSame(3, substr_count($response->getContent(), 'data-rating-average'));
        // 評価が無い人には★を付けない
        $this->assertStringNotContainsString('★', explode('文学 三子', $response->getContent())[1]);
    }

    public function test_average_is_not_shown_in_my_rank_or_home_top5(): void
    {
        $this->bestAnswers($this->me, [5]);

        $ranking = $this->actingAs($this->me)->get('/ranking')->getContent();
        $myRank = explode('aria-label="ランキング一覧"', explode('aria-label="あなたの順位"', $ranking)[1])[0];
        $this->assertStringNotContainsString('ベストアンサー評価の平均', $myRank);
        $this->assertStringNotContainsString('5.0', $myRank);

        $this->actingAs($this->me)->get('/home')->assertOk()
            ->assertDontSee('ベストアンサー評価の平均')
            ->assertDontSee('data-rating-average', false);
    }

    public function test_higher_average_does_not_change_rank_or_order(): void
    {
        $many = User::factory()->create(['name' => '多い']);
        Answer::factory()->for($many)->count(4)->create();
        $this->bestAnswers($many, [1]);
        $high = User::factory()->create(['name' => '高評価']);
        $this->bestAnswers($high, [5, 5]);

        $rows = app(RankingService::class)->top()->map(fn (RankingEntry $e) => [$e->name, $e->rank, $e->averageRating()])->all();

        $this->assertSame([['多い', 1, 1.0], ['高評価', 2, 5.0]], $rows);
    }

    public function test_ranking_page_query_count_does_not_grow_with_users(): void
    {
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->me)->get('/ranking')->assertOk();
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };

        $this->bestAnswers(User::factory()->create(), [4]);
        $few = $count();

        foreach (range(1, 8) as $i) {
            $this->bestAnswers(User::factory()->create(), [$i % 5 + 1, 3]);
        }

        $this->assertSame($few, $count());
    }
}
