<?php

namespace Tests\Feature\Reports;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G7 ⑨ 通報ボタンと G17 通報シート（表示・エラー時の開き直し・通報者の秘匿）
 */
class ReportSheetTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private User $viewer;

    private Question $question;

    private Answer $best;

    private Answer $answer;

    private Answer $viewersAnswer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->author = User::factory()->create(['name' => '質問者さん']);
        $this->viewer = User::factory()->create(['name' => '閲覧者さん']);
        $this->question = Question::factory()->for($this->author)->resolved()->create(['body' => '質問の本文です']);
        $this->best = Answer::factory()->for($this->question)->best(5)->create(['body' => 'ベストの回答']);
        $this->answer = Answer::factory()->for($this->question)->create(['body' => '他人の回答']);
        $this->viewersAnswer = Answer::factory()->for($this->question)->for($this->viewer)->create(['body' => '閲覧者の回答']);
    }

    private function show(?User $as = null)
    {
        return $this->actingAs($as ?? $this->viewer)->get(route('questions.show', $this->question));
    }

    public function test_report_buttons_for_others_posts_including_best_answer_but_not_own(): void
    {
        $html = $this->show()->assertOk()->getContent();

        $this->assertStringContainsString('data-bs-target="#report-sheet-question"', $html);
        $this->assertStringContainsString('data-bs-target="#report-sheet-answer-'.$this->best->id.'"', $html);
        $this->assertStringContainsString('data-bs-target="#report-sheet-answer-'.$this->answer->id.'"', $html);
        $this->assertStringNotContainsString('report-sheet-answer-'.$this->viewersAnswer->id, $html); // 自分の回答
    }

    public function test_question_author_has_no_report_button_on_own_question(): void
    {
        $html = $this->show($this->author)->getContent();

        $this->assertStringNotContainsString('report-sheet-question', $html);
        $this->assertStringContainsString('report-sheet-answer-'.$this->answer->id, $html);
    }

    public function test_sheet_shows_target_reasons_detail_and_buttons(): void
    {
        $this->show()->assertSeeTextInOrder([
            'この投稿を通報する',
            '通報内容は管理者のみが確認します。通報者の名前が相手に知られることはありません。',
            '質問：質問者さん', '質問の本文です',
            '通報理由（必須）', '不適切な内容', '誹謗中傷・嫌がらせ', 'スパム・宣伝', '個人情報の掲載', 'その他',
            '詳細（任意）', 'キャンセル', '通報する',
        ])->assertSee('placeholder="具体的な内容があれば入力してください（500文字以内）"', false)
            ->assertSee(route('reports.question', $this->question))
            ->assertSee(route('reports.answer', [$this->question, $this->answer]));
    }

    public function test_validation_error_reopens_only_the_target_sheet_with_input_kept(): void
    {
        $this->actingAs($this->viewer)
            ->from(route('questions.show', $this->question))
            ->post(route('reports.answer', [$this->question, $this->answer]), ['reason' => '', 'detail' => '入力した詳細', 'sheet' => 'answer-'.$this->answer->id]);

        $html = $this->show()->getContent();
        $sheet = $this->sheetHtml($html, 'report-sheet-answer-'.$this->answer->id);

        $this->assertMatchesRegularExpression('/id="report-sheet-answer-'.$this->answer->id.'"[^>]*data-mq-autoshow/', $html);
        $this->assertStringContainsString('通報理由を選択してください', $sheet);
        $this->assertStringContainsString('入力した詳細', $sheet);
        $this->assertSame(1, substr_count($html, '通報理由を選択してください'), '他のシートや画面上部には出さない');
        $this->assertSame(1, substr_count($html, 'data-mq-autoshow'));
    }

    public function test_duplicate_report_message_is_shown_in_the_sheet_with_reason_kept(): void
    {
        Report::factory()->create(['user_id' => $this->viewer->id, 'question_id' => $this->question->id]);

        $this->actingAs($this->viewer)
            ->from(route('questions.show', $this->question))
            ->post(route('reports.question', $this->question), ['reason' => 4, 'sheet' => 'question']);

        $sheet = $this->sheetHtml($this->show()->getContent(), 'report-sheet-question');
        $this->assertStringContainsString('この投稿は既に通報済みです', $sheet);
        $this->assertMatchesRegularExpression('/value="4"\s+id="report-sheet-question-reason-4"\s+checked/', $sheet);
    }

    public function test_success_message_after_report(): void
    {
        $this->actingAs($this->viewer)
            ->post(route('reports.question', $this->question), ['reason' => 1, 'sheet' => 'question'])
            ->assertRedirect(route('questions.show', $this->question));

        $this->show()->assertSee('通報を受け付けました');
    }

    /**
     * 通報者を相手に知られない：G7・G4・G11 のどこにも、通報者の名前・ID・メール・「通報済み」が出ない
     */
    public function test_reporter_identity_and_reported_state_are_never_shown_to_others(): void
    {
        $reporter = User::factory()->create(['name' => '通報したヒミツさん', 'email' => 'secret-reporter@example.com']);
        Report::factory()->create(['user_id' => $reporter->id, 'question_id' => $this->question->id, 'detail' => '通報の詳細ヒミツ']);
        Report::factory()->forAnswer($this->answer)->create(['user_id' => $reporter->id]);

        $pages = [
            [$this->author, route('questions.show', $this->question)],
            [$this->answer->user, route('questions.show', $this->question)],
            [$this->viewer, route('questions.show', $this->question)],
            [$this->author, route('home')],
            [$this->author, route('mypage')],
            [$this->answer->user, route('mypage', ['tab' => 'answers'])],
        ];

        foreach ($pages as [$user, $url]) {
            $response = $this->actingAs($user)->get($url)->assertOk();
            $raw = $response->getContent().json_encode($response->headers->all(), JSON_UNESCAPED_UNICODE);

            foreach (['通報したヒミツさん', 'secret-reporter@example.com', '通報の詳細ヒミツ', '通報済み', 'reporter', 'data-report'] as $secret) {
                $this->assertStringNotContainsString($secret, $raw, "{$url} に「{$secret}」が含まれている");
            }
            $this->assertDoesNotMatchRegularExpression('/(user_id|reporter_id)["\'=:\s]+'.$reporter->id.'\b/', $raw);
        }

        // 通報した本人の画面にも「通報済み」は出さない（ボタンはそのまま。送信すると重複のエラー）
        $this->actingAs($reporter)->get(route('questions.show', $this->question))
            ->assertDontSee('通報済み')
            ->assertSee('data-bs-target="#report-sheet-question"', false);
    }

    private function sheetHtml(string $html, string $id): string
    {
        $start = strpos($html, 'id="'.$id.'"');
        $this->assertNotFalse($start, "{$id} のシートが無い");
        $end = strpos($html, 'class="offcanvas offcanvas-bottom', $start + 1);

        return substr($html, $start, $end === false ? null : $end - $start);
    }
}
