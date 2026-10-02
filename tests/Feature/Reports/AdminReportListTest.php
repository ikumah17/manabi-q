<?php

namespace Tests\Feature\Reports;

use App\Enums\ReportReason;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G18 通報管理の一覧（初期表示は未対応・絞り込み・表示内容・エスケープ）
 */
class AdminReportListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create(['name' => '管理者']);
    }

    private function index(array $query = [])
    {
        return $this->actingAs($this->admin)->get(route('admin.reports.index', $query));
    }

    public function test_default_shows_pending_reports_only_newest_first(): void
    {
        Report::factory()->for(Question::factory()->state(['title' => '古い未対応の質問']))->create(['created_at' => now()->subDay()]);
        Report::factory()->for(Question::factory()->state(['title' => '新しい未対応の質問']))->create();
        Report::factory()->resolved()->for(Question::factory()->state(['title' => '対応済みの質問']))->create();

        $this->index()
            ->assertOk()
            ->assertSeeTextInOrder(['通報管理', '状態', '通報理由', '検索', '通報日時', '種別', '対象の内容', '通報理由', '通報者', '状態', '操作'])
            ->assertSeeInOrder(['新しい未対応の質問', '古い未対応の質問'])
            ->assertDontSee('対応済みの質問')
            ->assertSee('<option value="pending" selected>未対応</option>', false);
    }

    public function test_status_and_reason_filters(): void
    {
        Report::factory()->for(Question::factory()->state(['title' => 'スパムの通報']))->create(['reason' => ReportReason::Spam]);
        Report::factory()->for(Question::factory()->state(['title' => '誹謗中傷の通報']))->create(['reason' => ReportReason::Harassment]);
        Report::factory()->resolved()->for(Question::factory()->state(['title' => '対応済みのスパム']))->create(['reason' => ReportReason::Spam]);

        $this->index(['status' => 'all'])->assertSee('スパムの通報')->assertSee('誹謗中傷の通報')->assertSee('対応済みのスパム');
        $this->index(['status' => 'resolved'])->assertDontSee('スパムの通報')->assertSee('対応済みのスパム');
        $this->index(['status' => 'all', 'reason' => ReportReason::Spam->value])
            ->assertSee('スパムの通報')->assertSee('対応済みのスパム')->assertDontSee('誹謗中傷の通報');
        $this->index(['status' => 'hack', 'reason' => 99])->assertOk()->assertSee('スパムの通報'); // 不正な値は初期値
    }

    public function test_row_shows_type_content_reason_detail_reporter_and_status(): void
    {
        $answer = Answer::factory()->create(['body' => 'この問題はネットで調べれば分かるでしょう、という回答の本文']);
        Report::factory()->forAnswer($answer)->for(User::factory()->state(['name' => '情報 花子']), 'reporter')
            ->create(['reason' => ReportReason::Harassment, 'detail' => '言い方がきつい']);

        $this->index()->assertSeeTextInOrder([
            '回答', 'この問題はネットで調べれば分かるでしょう', '誹謗中傷・嫌がらせ', '言い方がきつい', '情報 花子', '未対応',
        ]);
    }

    public function test_hidden_target_has_badge_and_content_is_still_shown_to_admin(): void
    {
        Report::factory()->for(Question::factory()->hidden()->state(['title' => '非表示にされた質問']))->create();

        $this->index()->assertSeeTextInOrder(['非表示', '非表示にされた質問']);
    }

    public function test_resolved_report_shows_resolved_date(): void
    {
        $this->travelTo(now()->setDate(now()->year, 9, 30)->setTime(10, 0));
        Report::factory()->resolved()->create();
        $this->travelBack();

        $this->index(['status' => 'resolved'])->assertSeeText('対応済み（09/30）');
    }

    public function test_twenty_per_page_with_conditions_kept(): void
    {
        foreach (range(1, 21) as $i) {
            Report::factory()->for(Question::factory()->state(['title' => sprintf('通報対象%02d', $i)]))
                ->create(['reason' => ReportReason::Spam, 'created_at' => now()->subMinutes(100 - $i)]);
        }

        $this->index(['reason' => 3])
            ->assertSee('通報対象21')->assertDontSee('通報対象01')
            ->assertSee('reason=3', false)->assertSee('rel="next"', false);

        $this->index(['reason' => 3, 'page' => 2])->assertSee('通報対象01');
    }

    public function test_empty_message(): void
    {
        $this->index()->assertSee('該当する通報はありません');
    }

    public function test_reporter_name_detail_and_contents_are_html_escaped(): void
    {
        $question = Question::factory()->create(['title' => '<script>alert("title")</script>']);
        $answer = Answer::factory()->create(['body' => '<script>alert("body")</script>']);
        $reporter = User::factory()->create(['name' => '<script>alert(1)</script>']);
        Report::factory()->for($question)->for($reporter, 'reporter')->create(['detail' => '<script>alert("detail")</script>']);
        Report::factory()->forAnswer($answer)->for($reporter, 'reporter')->create();

        $html = $this->index()->getContent();

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;detail&quot;)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;title&quot;)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;body&quot;)&lt;/script&gt;', $html);
    }

    public function test_admin_menu_links_to_report_management(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee(route('admin.reports.index'));
    }

    public function test_general_user_gets_403_and_guest_goes_to_login(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.reports.index'))->assertForbidden();
        auth()->logout();
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
    }
}
