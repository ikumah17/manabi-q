<?php

namespace Tests\Feature\Reports;

use App\Actions\SubmitReport;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\ReportController;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G17 通報の登録（重複防止・自分の投稿・非表示の投稿・入力チェック）
 */
class ReportSubmitTest extends TestCase
{
    use RefreshDatabase;

    private User $reporter;

    private Question $question;

    private Answer $answer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->reporter = User::factory()->create();
        $this->question = Question::factory()->create();
        $this->answer = Answer::factory()->for($this->question)->create();
    }

    private function reportQuestion(array $data = [], ?User $as = null, ?Question $question = null)
    {
        $question ??= $this->question;

        return $this->actingAs($as ?? $this->reporter)
            ->from(route('questions.show', $question))
            ->post(route('reports.question', $question), array_merge(['reason' => 2, 'detail' => '', 'sheet' => 'question'], $data));
    }

    private function reportAnswer(array $data = [], ?User $as = null, ?Answer $answer = null)
    {
        $answer ??= $this->answer;

        return $this->actingAs($as ?? $this->reporter)
            ->from(route('questions.show', $answer->question_id))
            ->post(route('reports.answer', [$answer->question_id, $answer]), array_merge(['reason' => 3, 'detail' => '', 'sheet' => 'answer-'.$answer->id], $data));
    }

    public function test_question_report_is_saved_and_returns_to_detail_with_message(): void
    {
        $this->reportQuestion(['reason' => 2, 'detail' => '暴言があります'])
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHas('success', '通報を受け付けました');

        $report = Report::sole();
        $this->assertSame($this->reporter->id, $report->user_id);
        $this->assertSame($this->question->id, $report->question_id);
        $this->assertNull($report->answer_id);
        $this->assertSame(ReportReason::Harassment, $report->reason);
        $this->assertSame('暴言があります', $report->detail);
        $this->assertSame(ReportStatus::Pending, $report->status);
    }

    public function test_answer_report_stores_null_question_id(): void
    {
        $this->reportAnswer(['reason' => 3])->assertSessionHas('success', '通報を受け付けました');

        $report = Report::sole();
        $this->assertNull($report->question_id);
        $this->assertSame($this->answer->id, $report->answer_id);
        $this->assertSame(ReportReason::Spam, $report->reason);
        $this->assertNull($report->detail);
    }

    public function test_status_cannot_be_set_from_form(): void
    {
        $this->reportQuestion(['status' => 1, 'user_id' => User::factory()->create()->id]);

        $report = Report::sole();
        $this->assertSame(ReportStatus::Pending, $report->status);
        $this->assertSame($this->reporter->id, $report->user_id);
    }

    public function test_duplicate_report_of_same_target_is_rejected_with_message(): void
    {
        $this->reportQuestion()->assertSessionHas('success');

        $this->reportQuestion(['reason' => 5, 'detail' => '2回目'])
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHasErrorsIn('report', ['reason' => 'この投稿は既に通報済みです'])
            ->assertSessionHasInput('sheet', 'question');

        $this->reportAnswer()->assertSessionHas('success');
        $this->reportAnswer()->assertSessionHasErrorsIn('report', ['reason' => ReportController::DUPLICATE_MESSAGE]);

        $this->assertSame(2, Report::count());
    }

    public function test_cannot_report_again_after_report_was_resolved(): void
    {
        Report::factory()->resolved()->create(['user_id' => $this->reporter->id, 'question_id' => $this->question->id]);

        $this->reportQuestion()->assertSessionHasErrorsIn('report', ['reason' => ReportController::DUPLICATE_MESSAGE]);
        $this->assertSame(1, Report::count());
    }

    public function test_other_users_can_report_the_same_target_and_question_and_answer_are_separate(): void
    {
        $this->reportQuestion()->assertSessionHas('success');
        $this->reportQuestion([], User::factory()->create())->assertSessionHas('success');
        $this->reportAnswer()->assertSessionHas('success'); // 同じ質問の回答は別の対象

        $this->assertSame(3, Report::count());
    }

    public function test_own_question_and_own_answer_cannot_be_reported(): void
    {
        $this->reportQuestion([], $this->question->user)->assertForbidden();
        $this->reportAnswer([], $this->answer->user)->assertForbidden();

        $this->assertSame(0, Report::count());
    }

    public function test_hidden_posts_cannot_be_reported(): void
    {
        $hiddenQuestion = Question::factory()->hidden()->create();
        $hiddenAnswer = Answer::factory()->for($this->question)->hidden()->create();
        $answerOfHiddenQuestion = Answer::factory()->for($hiddenQuestion)->create();

        $this->reportQuestion([], null, $hiddenQuestion)->assertNotFound();
        $this->reportAnswer([], null, $hiddenAnswer)->assertNotFound();
        $this->reportAnswer([], null, $answerOfHiddenQuestion)->assertNotFound();

        $this->assertSame(0, Report::count());
    }

    public function test_answer_must_belong_to_question_in_url(): void
    {
        $otherAnswer = Answer::factory()->create();

        $this->actingAs($this->reporter)
            ->post(route('reports.answer', [$this->question, $otherAnswer]), ['reason' => 1])
            ->assertNotFound();
    }

    public function test_reason_is_required_and_must_be_valid(): void
    {
        foreach (['', 0, 6, 'abc'] as $reason) {
            $this->reportQuestion(['reason' => $reason])
                ->assertSessionHasErrorsIn('report', ['reason' => '通報理由を選択してください']);
        }

        $this->assertSame(0, Report::count());
    }

    public function test_detail_is_optional_up_to_500_chars(): void
    {
        $this->reportQuestion(['detail' => Str::repeat('あ', 501)])
            ->assertSessionHasErrorsIn('report', ['detail' => '詳細は500文字以内で入力してください'])
            ->assertSessionHasInput('detail', Str::repeat('あ', 501));
        $this->assertSame(0, Report::count());

        $this->reportQuestion(['detail' => Str::repeat('あ', 500)])->assertSessionHas('success');
        $this->assertSame(500, mb_strlen(Report::sole()->detail));
    }

    public function test_guest_and_admin_cannot_report(): void
    {
        $this->post(route('reports.question', $this->question), ['reason' => 1])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('reports.question', $this->question), ['reason' => 1])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(0, Report::count());
    }

    /**
     * 同時送信：1件目の登録後に届いた2件目は、ロックを取ってから確かめ直すため登録されない
     */
    public function test_action_rechecks_duplicate_inside_locked_transaction(): void
    {
        $action = app(SubmitReport::class);

        $this->assertTrue($action->handle($this->reporter, $this->question, ReportReason::Spam, null));
        $this->assertFalse($action->handle($this->reporter, $this->question, ReportReason::Other, '同時の2件目'));
        $this->assertTrue($action->handle($this->reporter, $this->answer, ReportReason::Spam, null));
        $this->assertFalse($action->handle($this->reporter, $this->answer, ReportReason::Spam, null));

        $this->assertSame(2, Report::count());
    }

    public function test_action_locks_reporter_row_inside_its_own_transaction(): void
    {
        $baseLevel = DB::transactionLevel();
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = ['sql' => $query->sql, 'level' => DB::transactionLevel()];
        });

        app(SubmitReport::class)->handle($this->reporter, $this->question, ReportReason::Spam, null);

        // 最初にユーザー行を読み（MySQL では FOR UPDATE）、すべてトランザクションの中で実行される
        $this->assertStringContainsString('from "users"', $queries[0]['sql']);
        $this->assertSame([$baseLevel + 1], array_values(array_unique(array_column($queries, 'level'))));
    }
}
