<?php

namespace Tests\Feature\Admin;

use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use App\Services\Admin\DashboardStats;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G13 管理者ダッシュボード（集計の定義・月の境界・表示）
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(CarbonImmutable::create(2026, 10, 15, 12, 0, 0, 'Asia/Tokyo'));
        $this->admin = User::factory()->admin()->create(['name' => '管理者']);
    }

    public function test_summary_counts_follow_definitions(): void
    {
        User::factory()->count(3)->create();
        User::factory()->suspended()->create();          // 利用停止も数える
        User::factory()->admin()->create();              // 管理者は数えない
        $open = Question::factory()->count(2)->create();
        Question::factory()->resolved()->create();
        Question::factory()->hidden()->create();         // 非表示：総数には含め、未解決には含めない
        Answer::factory()->for($open[0])->count(2)->create();
        Answer::factory()->for($open[0])->hidden()->create();
        Report::factory()->count(2)->create();
        Report::factory()->resolved()->create();

        $summary = app(DashboardStats::class)->summary();

        // 質問：明示した 4 件 + 通報のファクトリが作った 3 件（総数は非表示を含む）。ユーザー数は次のテストで厳密に確認
        $this->assertSame(4 + 3, Question::count());
        $this->assertSame(Question::count(), $summary['questions']);
        $this->assertSame(3, $summary['answers']);
        $this->assertSame(2, $summary['pendingReports']);
        $this->assertSame(Question::where('status', 1)->where('is_resolved', 0)->count(), $summary['unresolved']);
        $this->assertSame(2 + 3, $summary['unresolved']);
    }

    public function test_summary_excludes_admins_from_user_count_exactly(): void
    {
        User::factory()->count(2)->create();
        User::factory()->suspended()->create();
        User::factory()->admin()->count(2)->create();

        $this->assertSame(3, app(DashboardStats::class)->summary()['users']);
    }

    public function test_monthly_questions_are_last_seven_months_including_current_with_jst_boundaries(): void
    {
        $at = fn (string $time) => CarbonImmutable::parse($time, 'Asia/Tokyo');
        Question::factory()->create(['created_at' => $at('2026-10-01 00:00:00')]); // 10月の最初
        Question::factory()->create(['created_at' => $at('2026-09-30 23:59:59')]); // 9月の最後
        Question::factory()->hidden()->create(['created_at' => $at('2026-09-15 10:00:00')]); // 非表示も数える
        Question::factory()->create(['created_at' => $at('2026-04-01 00:00:00')]); // 7か月前の最初
        Question::factory()->create(['created_at' => $at('2026-03-31 23:59:59')]); // 対象外

        $monthly = app(DashboardStats::class)->monthlyQuestions(7);

        $this->assertSame(['4月', '5月', '6月', '7月', '8月', '9月', '10月'], $monthly->pluck('label')->all());
        $this->assertSame([1, 0, 0, 0, 0, 2, 1], $monthly->pluck('count')->all());
    }

    public function test_monthly_labels_across_year_boundary(): void
    {
        $this->travelTo(CarbonImmutable::create(2027, 2, 10, 9, 0, 0, 'Asia/Tokyo'));

        $monthly = app(DashboardStats::class)->monthlyQuestions(7);

        $this->assertSame(['8月', '9月', '10月', '11月', '12月', '1月', '2月'], $monthly->pluck('label')->all());
        $this->assertSame(['2026-08', '2027-02'], [$monthly->first()['start'], $monthly->last()['start']]);
    }

    public function test_questions_by_category_in_display_order_including_hidden_and_zero(): void
    {
        $life = Category::factory()->create(['name' => '学校生活', 'sort_order' => 2]);
        $lecture = Category::factory()->create(['name' => '授業', 'sort_order' => 1]);
        Category::factory()->create(['name' => 'その他', 'sort_order' => 3]);
        Question::factory()->for($lecture)->count(2)->create();
        Question::factory()->for($lecture)->hidden()->create();
        Question::factory()->for($life)->create();

        $rows = app(DashboardStats::class)->questionsByCategory();

        $this->assertSame([['label' => '授業', 'count' => 3], ['label' => '学校生活', 'count' => 1], ['label' => 'その他', 'count' => 0]], $rows->all());
    }

    public function test_recent_posts_are_newest_five_of_questions_and_answers_with_hidden_flag(): void
    {
        $base = CarbonImmutable::now();
        $q = Question::factory()->create(['title' => '質問A', 'created_at' => $base->subMinutes(60)]);
        Answer::factory()->for($q)->create(['body' => '回答B', 'created_at' => $base->subMinutes(50)]);
        Question::factory()->hidden()->create(['title' => '非表示の質問C', 'created_at' => $base->subMinutes(40)]);
        Answer::factory()->for($q)->hidden()->create(['body' => '非表示の回答D', 'created_at' => $base->subMinutes(30)]);
        Question::factory()->create(['title' => '質問E', 'created_at' => $base->subMinutes(20)]);
        Answer::factory()->for($q)->create(['body' => '回答F', 'created_at' => $base->subMinutes(10)]);

        $posts = app(DashboardStats::class)->recentPosts(5);

        $this->assertSame(['回答F', '質問E', '非表示の回答D', '非表示の質問C', '回答B'], $posts->pluck('content')->all());
        $this->assertSame([false, false, true, true, false], $posts->pluck('hidden')->all());
    }

    public function test_dashboard_page_shows_cards_charts_and_recent_posts(): void
    {
        $category = Category::factory()->create(['name' => '授業', 'sort_order' => 1]);
        Question::factory()->for($category)->hidden()->create(['title' => '非表示の質問', 'created_at' => now()->subMinute()]);
        Report::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

        $response->assertSeeTextInOrder(['ダッシュボード', '総ユーザー数', '人', '総質問数', '総回答数', '未解決の質問', '未対応の通報',
            '質問投稿数の推移（月別）', 'カテゴリ別質問数', '最近の投稿', '質問', '非表示', '非表示の質問']);
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/data-chart=\'\{"type":"line","labels":\["4\\\\u6708"/', $html);
        $this->assertStringContainsString('"type":"bar"', $html);
        $this->assertStringContainsString('mq-stat-card__value is-alert', $html);
        $this->assertMatchesRegularExpression('/\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}/', $html); // 2026/10/15 11:59 形式
    }

    public function test_texts_are_escaped_in_recent_posts_and_chart_data(): void
    {
        Category::factory()->create(['name' => "</script>'\"<b>", 'sort_order' => 1]);
        Question::factory()->create(['title' => '<script>alert(1)</script>']);

        $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString("</script>'\"<b>", $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_general_user_gets_403(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }
}
