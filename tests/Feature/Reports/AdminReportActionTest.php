<?php

namespace Tests\Feature\Reports;

use App\Actions\HandleReport;
use App\Enums\PostStatus;
use App\Enums\ReportStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use App\Services\Ranking\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * G18 通報への対応（対象を非表示／対応済みにする）
 */
class AdminReportActionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function handle(string $route, Report $report, array $query = [])
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.reports.index', $query))
            ->post(route($route, $report));
    }

    public function test_hide_target_question_hides_it_and_resolves_all_pending_reports_on_it(): void
    {
        $question = Question::factory()->create(['title' => '不適切な質問']);
        $answer = Answer::factory()->for($question)->create();
        $report = Report::factory()->create(['question_id' => $question->id]);
        $sameTarget = Report::factory()->create(['question_id' => $question->id]);
        $answerReport = Report::factory()->forAnswer($answer)->create();  // 同じ質問の回答への通報は別対象
        $otherReport = Report::factory()->create();

        $this->handle('admin.reports.hide-target', $report, ['status' => 'pending', 'page' => 1])
            ->assertRedirect(route('admin.reports.index', ['status' => 'pending', 'page' => 1]))
            ->assertSessionHas('success', '対象の投稿を非表示にし、通報を対応済みにしました');

        $this->assertSame(PostStatus::Hidden, $question->refresh()->status);
        $this->assertSame(ReportStatus::Resolved, $report->refresh()->status);
        $this->assertSame(ReportStatus::Resolved, $sameTarget->refresh()->status);
        $this->assertSame(ReportStatus::Pending, $answerReport->refresh()->status);
        $this->assertSame(ReportStatus::Pending, $otherReport->refresh()->status);
        $this->assertTrue($answer->refresh()->isPublished());

        // 一般画面から消える
        $viewer = User::factory()->create();
        $this->actingAs($viewer)->get(route('questions.show', $question))->assertNotFound();
        $this->actingAs($viewer)->get(route('home'))->assertDontSee('不適切な質問');
    }

    public function test_hide_target_answer_hides_only_that_answer(): void
    {
        $question = Question::factory()->create();
        $answer = Answer::factory()->for($question)->create(['body' => '不適切な回答']);
        $report = Report::factory()->forAnswer($answer)->create();
        $sameAnswer = Report::factory()->forAnswer($answer)->create();
        $questionReport = Report::factory()->create(['question_id' => $question->id]);

        $this->handle('admin.reports.hide-target', $report)->assertSessionHas('success');

        $this->assertSame(PostStatus::Hidden, $answer->refresh()->status);
        $this->assertTrue($question->refresh()->isPublished());
        $this->assertSame(ReportStatus::Resolved, $sameAnswer->refresh()->status);
        $this->assertSame(ReportStatus::Pending, $questionReport->refresh()->status);

        $this->actingAs(User::factory()->create())->get(route('questions.show', $question))
            ->assertOk()
            ->assertDontSee('不適切な回答');
    }

    public function test_hiding_best_answer_keeps_question_resolved_and_changes_ranking_counts(): void
    {
        $answerer = User::factory()->create();
        $question = Question::factory()->resolved()->create();
        $best = Answer::factory()->for($question)->for($answerer)->best(5)->create(['body' => 'ベストの回答']);
        Answer::factory()->for($answerer)->count(9)->create();
        $this->assertSame(10, app(RankingService::class)->answerCount($answerer));

        $this->handle('admin.reports.hide-target', Report::factory()->forAnswer($best)->create());

        $this->assertTrue($question->refresh()->is_resolved);
        $this->assertSame(9, app(RankingService::class)->answerCount($answerer));
        $this->assertSame(0, app(RankingService::class)->forUser($answerer)->bestAnswerCount);
        $this->actingAs(User::factory()->create())->get(route('questions.show', $question))
            ->assertSee('解決済み')
            ->assertDontSee('ベストの回答');
    }

    public function test_resolve_marks_only_that_report_and_keeps_target_published(): void
    {
        $question = Question::factory()->create();
        $report = Report::factory()->create(['question_id' => $question->id]);
        $sameTarget = Report::factory()->create(['question_id' => $question->id]);

        $this->handle('admin.reports.resolve', $report)
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHas('success', '通報を対応済みにしました');

        $this->assertSame(ReportStatus::Resolved, $report->refresh()->status);
        $this->assertSame(ReportStatus::Pending, $sameTarget->refresh()->status);
        $this->assertTrue($question->refresh()->isPublished());
    }

    public function test_already_resolved_report_is_not_changed_again(): void
    {
        $question = Question::factory()->create();
        $report = Report::factory()->resolved()->create(['question_id' => $question->id]);

        $this->handle('admin.reports.hide-target', $report)->assertSessionHas('error', '既に対応済みです');
        $this->handle('admin.reports.resolve', $report)->assertSessionHas('error', '既に対応済みです');

        $this->assertTrue($question->refresh()->isPublished());
    }

    public function test_double_submit_is_handled_once(): void
    {
        $report = Report::factory()->create();

        $this->handle('admin.reports.resolve', $report)->assertSessionHas('success');
        $this->handle('admin.reports.resolve', $report)->assertSessionHas('error', '既に対応済みです');
    }

    public function test_action_rechecks_locked_row_when_model_is_stale(): void
    {
        $question = Question::factory()->create();
        $report = Report::factory()->create(['question_id' => $question->id]);
        $stale = Report::findOrFail($report->id); // 画面を開いた時点（未対応）

        $this->assertTrue(app(HandleReport::class)->resolve($report));
        $this->assertSame(ReportStatus::Pending, $stale->status);
        $this->assertFalse(app(HandleReport::class)->hideTarget($stale));

        $this->assertTrue($question->refresh()->isPublished());
    }

    public function test_actions_run_inside_their_own_transaction(): void
    {
        $report = Report::factory()->create();
        $baseLevel = DB::transactionLevel();
        $levels = [];
        DB::listen(function () use (&$levels) {
            $levels[] = DB::transactionLevel();
        });

        app(HandleReport::class)->hideTarget($report);

        $this->assertSame([$baseLevel + 1], array_values(array_unique($levels)));
    }

    public function test_hide_button_opens_confirm_modal_and_is_not_shown_for_hidden_target(): void
    {
        $visible = Report::factory()->for(Question::factory()->state(['title' => '公開中の質問']))->create();
        Report::factory()->for(Question::factory()->hidden()->state(['title' => '非表示の質問']))->create();

        $html = $this->actingAs($this->admin)->get(route('admin.reports.index'))->getContent();

        $this->assertSame(1, substr_count($html, '>対象を非表示</button>'));
        $this->assertStringContainsString('data-confirm-action="'.route('admin.reports.hide-target', $visible).'"', $html);
        $this->assertStringContainsString('data-confirm-message="質問：公開中の質問"', $html);
        $this->assertSame(2, substr_count($html, '>対応済みにする</button>'));
        $this->assertStringContainsString('id="hide-target-modal"', $html);
        $this->assertStringContainsString('対象の投稿を非表示にしますか？', $html);
        $this->assertStringNotContainsString('data-confirm="', $html, 'ブラウザ標準の confirm() は使わない');
    }

    public function test_confirm_message_attribute_is_escaped(): void
    {
        Report::factory()->for(Question::factory()->state(['title' => '"><script>alert(1)</script>']))->create();

        $html = $this->actingAs($this->admin)->get(route('admin.reports.index'))->getContent();

        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('data-confirm-message="質問：&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
    }

    public function test_general_user_and_guest_cannot_handle_reports(): void
    {
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('admin.reports.hide-target', $report))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('admin.reports.resolve', $report))->assertForbidden();
        auth()->logout();
        $this->post(route('admin.reports.resolve', $report))->assertRedirect(route('login'));

        $this->assertSame(ReportStatus::Pending, $report->refresh()->status);
    }
}
