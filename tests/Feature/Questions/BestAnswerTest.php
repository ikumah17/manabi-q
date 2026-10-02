<?php

namespace Tests\Feature\Questions;

use App\Actions\ChooseBestAnswer;
use App\Enums\PostStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * G9 ベストアンサー評価（★1〜5。決定後は変更不可・質問は解決済み）
 */
class BestAnswerTest extends TestCase
{
    use RefreshDatabase;

    private User $author;

    private Question $question;

    private Answer $answer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->author = User::factory()->create();
        $this->question = Question::factory()->for($this->author)->create();
        $this->answer = Answer::factory()->for($this->question)->create();
    }

    private function choose(Answer $answer, mixed $rating = 4, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->author)
            ->from(route('questions.show', $this->question))
            ->post(route('answers.best', [$this->question, $answer]), ['rating' => $rating, 'sheet' => $answer->id]);
    }

    public function test_author_chooses_best_answer_with_rating_and_question_is_resolved(): void
    {
        $this->choose($this->answer, 4)
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHas('success', 'ベストアンサーを決定しました');

        $this->answer->refresh();
        $this->assertTrue($this->answer->is_best);
        $this->assertSame(4, $this->answer->rating);
        $this->assertTrue($this->question->refresh()->is_resolved);
    }

    public function test_rating_is_required_and_between_1_and_5(): void
    {
        $this->choose($this->answer, '')
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHasErrorsIn('best', ['rating' => '評価を選択してください'])
            ->assertSessionHasInput('sheet', $this->answer->id);

        $this->choose($this->answer, 0)->assertSessionHasErrorsIn('best', 'rating');
        $this->choose($this->answer, 6)->assertSessionHasErrorsIn('best', 'rating');
        $this->choose($this->answer, 'abc')->assertSessionHasErrorsIn('best', 'rating');

        $this->assertFalse($this->answer->refresh()->is_best);
        $this->assertFalse($this->question->refresh()->is_resolved);
    }

    public function test_validation_error_reopens_sheet_on_detail(): void
    {
        $this->choose($this->answer, '');

        $response = $this->actingAs($this->author)->get(route('questions.show', $this->question))
            ->assertSee('評価を選択してください');

        // 対象の回答のシートに自動で開く印（data-mq-autoshow）が付く
        $this->assertMatchesRegularExpression(
            '/id="best-sheet-'.$this->answer->id.'"[^>]*data-mq-autoshow/',
            $response->getContent(),
        );
    }

    public function test_cannot_choose_twice_or_change_after_decision(): void
    {
        $other = Answer::factory()->for($this->question)->create();

        $this->choose($this->answer, 5)->assertSessionHas('success');

        // 2回目（同じ回答・別の回答とも）は拒否され、決定内容は変わらない
        $this->choose($this->answer, 1)->assertForbidden();
        $this->choose($other, 3)->assertForbidden();

        $this->assertSame(5, $this->answer->refresh()->rating);
        $this->assertFalse($other->refresh()->is_best);
        $this->assertSame(1, Answer::where('question_id', $this->question->id)->where('is_best', true)->count());
    }

    public function test_only_question_author_can_choose(): void
    {
        $this->choose($this->answer, 4, User::factory()->create())->assertForbidden();
        $this->choose($this->answer, 4, $this->answer->user)->assertForbidden();

        $this->assertFalse($this->question->refresh()->is_resolved);
    }

    public function test_author_cannot_choose_own_answer(): void
    {
        $own = Answer::factory()->for($this->question)->for($this->author)->create();

        $this->choose($own, 5)->assertForbidden();

        $this->assertFalse($own->refresh()->is_best);
        $this->assertFalse($this->question->refresh()->is_resolved);
    }

    public function test_cannot_choose_hidden_answer_or_answer_of_another_question(): void
    {
        $hidden = Answer::factory()->for($this->question)->hidden()->create();
        $this->choose($hidden, 5)->assertForbidden();

        $otherQuestionsAnswer = Answer::factory()->create();
        $this->actingAs($this->author)
            ->post(route('answers.best', [$this->question, $otherQuestionsAnswer]), ['rating' => 5])
            ->assertNotFound();

        $this->assertFalse($this->question->refresh()->is_resolved);
    }

    public function test_cannot_choose_on_hidden_question(): void
    {
        $this->question->status = PostStatus::Hidden;
        $this->question->save();

        $this->choose($this->answer, 5)->assertNotFound();
        $this->assertFalse($this->answer->refresh()->is_best);
    }

    /**
     * 画面を開いた後に別のリクエストで決定済みになった（同時送信）場合：
     * 古い状態の質問を渡しても、ロックした最新の行で判定して拒否する
     */
    public function test_action_rechecks_locked_row_and_rejects_when_already_resolved(): void
    {
        $staleQuestion = Question::findOrFail($this->question->id); // まだ未解決の状態で読み込んだもの
        $first = Answer::factory()->for($this->question)->create();

        $this->assertTrue(app(ChooseBestAnswer::class)->handle($this->question, $first, 5));

        $this->assertFalse($staleQuestion->is_resolved);
        $this->assertFalse(app(ChooseBestAnswer::class)->handle($staleQuestion, $this->answer, 2));

        $this->assertFalse($this->answer->refresh()->is_best);
        $this->assertNull($this->answer->rating);
        $this->assertSame(1, Answer::where('question_id', $this->question->id)->where('is_best', true)->count());
    }

    public function test_action_rejects_answer_hidden_after_page_was_opened(): void
    {
        $staleAnswer = Answer::findOrFail($this->answer->id);
        Answer::whereKey($this->answer->id)->update(['status' => PostStatus::Hidden->value]);

        $this->assertFalse(app(ChooseBestAnswer::class)->handle($this->question, $staleAnswer, 5));
        $this->assertFalse($this->question->refresh()->is_resolved);
    }

    public function test_action_rejects_own_answer(): void
    {
        $own = Answer::factory()->for($this->question)->for($this->author)->create();

        $this->assertFalse(app(ChooseBestAnswer::class)->handle($this->question, $own, 5));
        $this->assertFalse($this->question->refresh()->is_resolved);
    }

    public function test_action_reads_and_updates_rows_inside_its_own_transaction(): void
    {
        // RefreshDatabase がテスト全体をトランザクションで包むため、ここが 1 段目
        $baseLevel = DB::transactionLevel();
        $levels = [];
        DB::listen(function () use (&$levels) {
            $levels[] = DB::transactionLevel();
        });

        $this->assertTrue(app(ChooseBestAnswer::class)->handle($this->question, $this->answer, 5));

        $this->assertNotEmpty($levels);
        $this->assertSame($baseLevel + 1, min($levels), 'ロック付きの読み込みと更新がトランザクションの中で実行されていない');
    }
}
