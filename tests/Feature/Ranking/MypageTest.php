<?php

namespace Tests\Feature\Ranking;

use App\Models\Answer;
use App\Models\Category;
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
 * G11 マイページ
 */
class MypageTest extends TestCase
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
        $this->me = User::factory()->create(['name' => '山田 太郎', 'email' => 'yamada@example.com']);
    }

    private function answers(User $user, int $count, int $best = 0): void
    {
        Answer::factory()->for($user)->count($count - $best)->create();
        Answer::factory()->for($user)->best()->count($best)->create();
    }

    public function test_user_info_counts_rank_and_stamps(): void
    {
        $this->answers(User::factory()->create(), 80);
        $this->answers(User::factory()->create(), 70);
        $this->answers($this->me, 64, 12);
        Answer::factory()->for($this->me)->hidden()->count(5)->create();                       // 数えない
        Answer::factory()->for($this->me)->for(Question::factory()->hidden())->count(5)->create(); // 数えない
        Question::factory()->for($this->me)->count(8)->create();
        Question::factory()->for($this->me)->hidden()->create();                                 // 数えない

        $this->actingAs($this->me)->get('/mypage')
            ->assertOk()
            ->assertSeeTextInOrder([
                '山', '山田 太郎', 'yamada@example.com', '質問 8', '回答 64', 'BA 12', '3位',
                '獲得スタンプ',
                '★', '10回答達成', '獲得済み',
                '★', '50回答達成', '獲得済み',
                '未獲得', '100回答達成', 'あと36回答',
                '自分の質問（8）', '自分の回答（64）',
                'ログアウト',
            ])
            // ロック中のスタンプには鍵のアイコン（SVG の data-icon）を出す
            ->assertSeeInOrder(['50回答達成', 'data-icon="lock"', '100回答達成'], false);
    }

    public function test_new_user_has_no_rank_and_all_stamps_locked(): void
    {
        $this->actingAs($this->me)->get('/mypage')
            ->assertSeeTextInOrder(['質問 0', '回答 0', 'BA 0', '順位 −'])
            ->assertSeeTextInOrder(['10回答達成', 'あと10回答', '50回答達成', 'あと50回答', '100回答達成', 'あと100回答'])
            ->assertDontSee('獲得済み')
            ->assertSee('まだ質問はありません');
    }

    public function test_stamp_boundary_at_ten_answers(): void
    {
        $this->answers($this->me, 9);
        $this->actingAs($this->me)->get('/mypage')->assertSeeText('あと1回答')->assertDontSee('獲得済み');

        $this->answers($this->me, 1);
        $this->actingAs($this->me)->get('/mypage')->assertSeeTextInOrder(['10回答達成', '獲得済み', 'あと40回答']);
    }

    public function test_questions_tab_lists_only_my_published_questions_ten_per_page(): void
    {
        $category = Category::factory()->create(['name' => '授業']);
        foreach (range(1, 11) as $i) {
            Question::factory()->for($this->me)->for($category)->create(['title' => sprintf('自分の質問%02d', $i), 'created_at' => now()->subMinutes(100 - $i)]);
        }
        Question::factory()->for($this->me)->hidden()->create(['title' => '非表示の自分の質問']);
        Question::factory()->create(['title' => '他人の質問']);

        $this->actingAs($this->me)->get('/mypage')
            ->assertSeeInOrder(['自分の質問11', '自分の質問02'])
            ->assertDontSee('自分の質問01')
            ->assertDontSee('非表示の自分の質問')
            ->assertDontSee('他人の質問')
            ->assertSee('rel="next"', false);

        $this->actingAs($this->me)->get('/mypage?page=2')->assertSee('自分の質問01');
    }

    public function test_answers_tab_lists_one_card_per_answer_with_link_to_the_answer(): void
    {
        $question = Question::factory()->create(['title' => '回答した質問', 'created_at' => now()->subYear()->startOfYear()]);
        $first = Answer::factory()->for($this->me)->for($question)->create(['created_at' => now()->startOfYear()->addDays(2)]);
        Answer::factory()->for($this->me)->for($question)->create();
        Answer::factory()->for($this->me)->for($question)->hidden()->create();
        Answer::factory()->for($this->me)->for(Question::factory()->hidden()->state(['title' => '非表示の質問']))->create();

        $response = $this->actingAs($this->me)->get('/mypage?tab=answers')
            ->assertSeeText('自分の回答（2）')
            ->assertSee(route('questions.show', $question).'#answer-'.$first->id)
            ->assertSee($first->created_at->format('m/d'))
            ->assertDontSee('非表示の質問');

        $this->assertMatchesRegularExpression('/aria-current="page"\s*>自分の回答/u', $response->getContent());
        $this->assertSame(2, substr_count($response->getContent(), '回答した質問'));
    }

    public function test_logout_button_logs_out_to_login_screen(): void
    {
        $this->actingAs($this->me)->get('/mypage')->assertSee(route('logout'));

        $this->actingAs($this->me)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_temporary_logout_button_was_removed_from_home(): void
    {
        $this->actingAs($this->me)->get('/home')
            ->assertDontSee('ログアウト')
            ->assertDontSee(route('logout'));
    }

    public function test_home_ranking_and_mypage_use_the_same_aggregation(): void
    {
        $users = collect(range(1, 5))->map(fn ($i) => User::factory()->create(['name' => "U{$i}"]));
        foreach ($users as $i => $user) {
            $this->answers($user, 6 - $i, $i % 2);
        }
        $this->answers($this->me, 4, 2);                                                 // U3 と同数（6件・5件の2人に次ぐ3位）
        Answer::factory()->for($this->me)->for(Question::factory()->hidden())->count(9)->create(); // 数えない

        $service = app(RankingService::class)->forUser($this->me);
        $ranking = $this->actingAs($this->me)->get('/ranking')->viewData('mine');
        $mypage = $this->actingAs($this->me)->get('/mypage');
        $home = collect($this->actingAs($this->me)->get('/home')->viewData('topRankers'))->firstWhere('userId', $this->me->id);

        $expected = [$service->rank, $service->answerCount, $service->bestAnswerCount];
        $this->assertSame([3, 4, 2], $expected);
        $this->assertSame($expected, [$ranking->rank, $ranking->answerCount, $ranking->bestAnswerCount]);
        $this->assertSame($expected, [$mypage->viewData('rank'), $mypage->viewData('answerCount'), $mypage->viewData('bestAnswerCount')]);
        $this->assertSame($expected, [$home->rank, $home->answerCount, $home->bestAnswerCount]);
    }

    public function test_mypage_renders_values_from_ranking_service(): void
    {
        $entry = new RankingEntry($this->me->id, '山田 太郎', 7, 321, 45, collect());
        $this->app->instance(RankingService::class, new class($entry) extends RankingService
        {
            public function __construct(private RankingEntry $entry) {}

            public function forUser(User $user): ?RankingEntry
            {
                return $this->entry;
            }

            public function answerCount(User $user): int
            {
                return $this->entry->answerCount;
            }

            public function top(int $limit = 5): Collection
            {
                return collect([$this->entry]);
            }

            public function paginate(int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
            {
                return new LengthAwarePaginator([$this->entry], 1, $perPage);
            }
        });

        $this->actingAs($this->me)->get('/mypage')->assertSeeTextInOrder(['回答 321', 'BA 45', '7位', '自分の回答（321）']);
    }

    public function test_guest_and_admin_cannot_open_mypage(): void
    {
        $this->get('/mypage')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get('/mypage')->assertRedirect(route('admin.dashboard'));
    }
}
