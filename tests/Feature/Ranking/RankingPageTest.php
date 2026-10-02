<?php

namespace Tests\Feature\Ranking;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Stamp;
use App\Models\User;
use App\Services\Ranking\RankingEntry;
use App\Services\Ranking\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * G10 回答者ランキング・G4 ⑨⑩ TOP5
 */
class RankingPageTest extends TestCase
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

    private function answers(User $user, int $count, int $best = 0): void
    {
        Answer::factory()->for($user)->count($count - $best)->create();
        Answer::factory()->for($user)->best()->count($best)->create();
    }

    public function test_ranking_page_shows_my_rank_list_best_answers_and_stamps(): void
    {
        $this->answers(User::factory()->create(['name' => '情報 花子']), 128, 35);
        $this->answers(User::factory()->create(['name' => '経済 一郎']), 97, 28);
        $this->answers($this->me, 64, 12);
        $this->answers(User::factory()->create(['name' => '文学 三子']), 9, 1);

        $response = $this->actingAs($this->me)->get('/ranking')->assertOk();

        $response->assertSeeTextInOrder([
            '回答数の多い順に表示しています。回答数に応じてスタンプ（10・50・100回答）を獲得できます。',
            'あなたの順位', '3位', '回答数 64件', 'ベストアンサー 12件',
            '1', '情報 花子', 'ベストアンサー 35件', '★ 10・50・100', '128', '回答',
            '2', '経済 一郎', 'ベストアンサー 28件', '★ 10・50', '97',
            '3', '山田 太郎', 'ベストアンサー 12件', '★ 10・50', '64',
            '4', '文学 三子', 'ベストアンサー 1件', '9',
        ]);
        // 自分の行だけ色付き。獲得スタンプが無い人には★を出さない
        $this->assertSame(1, substr_count($response->getContent(), 'mq-ranking-list__item is-mine'));
        $this->assertStringNotContainsString('★ 10', explode('文学 三子', $response->getContent())[1]);
        $response->assertSee('aria-current="page"', false); // 下部タブの「ランキング」
    }

    public function test_user_without_answers_sees_dash(): void
    {
        $this->answers(User::factory()->create(), 3);

        $this->actingAs($this->me)->get('/ranking')
            ->assertSeeTextInOrder(['あなたの順位', '−', 'まだ回答がありません'])
            ->assertDontSee('is-mine');
    }

    public function test_empty_ranking_message(): void
    {
        $this->actingAs($this->me)->get('/ranking')->assertSee('まだランキングはありません');
    }

    public function test_twenty_per_page_and_my_rank_on_every_page(): void
    {
        foreach (range(1, 22) as $i) {
            $this->answers(User::factory()->create(['name' => sprintf('回答者%02d', $i)]), 40 - $i);
        }
        $this->answers($this->me, 1); // 23位

        $this->actingAs($this->me)->get('/ranking')
            ->assertSee('回答者20')
            ->assertDontSee('回答者21')
            ->assertSeeText('23位');

        $this->actingAs($this->me)->get('/ranking?page=2')
            ->assertSeeTextInOrder(['23位', '21', '回答者21', '22', '回答者22', '23', '山田 太郎'])
            ->assertDontSee('回答者20');
    }

    public function test_top5_on_home_shows_ranking_and_link_to_g10(): void
    {
        foreach (['A' => 9, 'B' => 8, 'C' => 7, 'D' => 6, 'E' => 5, 'F' => 4] as $name => $count) {
            $this->answers(User::factory()->create(['name' => "回答者{$name}"]), $count);
        }

        $this->actingAs($this->me)->get('/home')
            ->assertSeeTextInOrder([
                '回答者ランキング TOP5',
                '1位', '回答者A', '9件',
                '2位', '回答者B', '8件',
                '5位', '回答者E', '5件',
                'ランキングをもっと見る',
            ])
            ->assertDontSee('回答者F')
            ->assertSee(route('ranking'));
    }

    public function test_top5_empty_message_on_home(): void
    {
        $this->actingAs($this->me)->get('/home')
            ->assertSeeTextInOrder(['回答者ランキング TOP5', 'まだランキングはありません', 'ランキングをもっと見る']);
    }

    public function test_home_and_ranking_page_use_the_same_aggregation(): void
    {
        // 非表示の回答・非表示の質問への回答・管理者・利用停止を含むデータ
        $users = collect(range(1, 7))->map(fn ($i) => User::factory()->create(['name' => "U{$i}"]));
        foreach ($users as $i => $user) {
            $this->answers($user, 7 - $i, $i % 2);
        }
        Answer::factory()->for($users[6])->hidden()->count(10)->create();
        Answer::factory()->for($users[5])->for(Question::factory()->hidden())->count(10)->create();
        $this->answers(User::factory()->admin()->create(), 20);
        $this->answers(User::factory()->suspended()->create(), 20);

        $home = $this->actingAs($this->me)->get('/home')->viewData('topRankers');
        $ranking = $this->actingAs($this->me)->get('/ranking')->viewData('entries');

        $this->assertSame(
            array_slice(self::plain($ranking->items()), 0, 5),
            self::plain($home),
        );
        $this->assertSame(self::plain($ranking->items()), self::plain(app(RankingService::class)->paginate()->items()));
    }

    /**
     * 比較用に値だけを取り出す（ID・名前・順位・回答数・BA数・獲得スタンプ）
     *
     * @param  iterable<RankingEntry>  $entries
     */
    private static function plain(iterable $entries): array
    {
        return collect($entries)->map(fn (RankingEntry $e) => [
            $e->userId, $e->name, $e->rank, $e->answerCount, $e->bestAnswerCount, $e->earnedStamps->pluck('id')->all(),
        ])->values()->all();
    }

    public function test_pages_render_values_from_ranking_service(): void
    {
        // 集計を差し替えると G4・G10 の両方の表示が変わる（画面ごとに独自の集計をしていない）
        $entry = new RankingEntry($this->me->id, '差し替えた名前', 7, 321, 45, collect());
        $this->app->instance(RankingService::class, new class($entry) extends RankingService
        {
            public function __construct(private RankingEntry $entry) {}

            public function top(int $limit = 5): Collection
            {
                return collect([$this->entry]);
            }

            public function paginate(int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
            {
                return new LengthAwarePaginator([$this->entry], 1, $perPage);
            }

            public function forUser(User $user): ?RankingEntry
            {
                return $this->entry;
            }
        });

        $this->actingAs($this->me)->get('/home')->assertSeeTextInOrder(['7位', '差し替えた名前', '321件']);
        $this->actingAs($this->me)->get('/ranking')->assertSeeTextInOrder(['7位', '回答数 321件', 'ベストアンサー 45件', '差し替えた名前']);
    }

    public function test_guest_and_admin_cannot_open_ranking(): void
    {
        $this->get('/ranking')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/ranking')->assertRedirect(route('admin.dashboard'));
    }
}
