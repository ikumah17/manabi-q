<?php

namespace Tests\Feature\Questions;

use App\Actions\ChooseBestAnswer;
use App\Http\Controllers\AnswerController;
use App\Models\Answer;
use App\Models\Like;
use App\Models\Question;
use App\Models\Report;
use App\Models\Stamp;
use App\Models\User;
use App\Services\Ranking\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G20 回答編集・G21 回答の削除（本人のみ。ベストアンサーは不可）
 */
class AnswerEditTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private User $asker;

    private Question $question;

    private Answer $answer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->me = User::factory()->create();
        $this->asker = User::factory()->create();
        $this->question = Question::factory()->for($this->asker)->create(['title' => '回答した質問']);
        $this->answer = Answer::factory()->for($this->question)->for($this->me)->create(['body' => '元の回答']);
    }

    private function editUrl(?Answer $answer = null): string
    {
        $answer ??= $this->answer;

        return route('answers.edit', [$answer->question_id, $answer]);
    }

    private function updateUrl(?Answer $answer = null): string
    {
        $answer ??= $this->answer;

        return route('answers.update', [$answer->question_id, $answer]);
    }

    private function destroyUrl(?Answer $answer = null): string
    {
        $answer ??= $this->answer;

        return route('answers.destroy', [$answer->question_id, $answer]);
    }

    public function test_owner_sees_edit_form_with_question_title_body_and_stamp(): void
    {
        $stamp = Stamp::factory()->requiring(10)->create(['name' => '10回答達成']);
        $answer = Answer::factory()->for($this->question)->for($this->me)->create(['stamp_id' => $stamp->id]);

        $this->actingAs($this->me)->get($this->editUrl($answer))
            ->assertOk()
            ->assertSeeTextInOrder(['回答を編集', '回答した質問', '回答本文', '添付スタンプ（変更できません）', '10回答達成', '更新する'])
            ->assertDontSee('name="stamp_id"', false);

        $this->actingAs($this->me)->get($this->editUrl())
            ->assertSeeText('元の回答')
            ->assertDontSee('添付スタンプ');
    }

    public function test_owner_updates_body_only(): void
    {
        $stamp = Stamp::factory()->requiring(10)->create();
        $other = Stamp::factory()->requiring(50)->create();
        $this->answer->stamp_id = $stamp->id;
        $this->answer->save();

        $this->actingAs($this->me)->put($this->updateUrl(), ['body' => '直した回答', 'stamp_id' => $other->id])
            ->assertRedirect(route('questions.show', $this->question).'#answer-'.$this->answer->id)
            ->assertSessionHas('success', '回答を更新しました');

        $this->assertSame(['直した回答', $stamp->id], [$this->answer->refresh()->body, $this->answer->stamp_id]);
    }

    public function test_validation_errors_keep_the_answer_unchanged(): void
    {
        foreach (['', str_repeat('あ', 2001)] as $body) {
            $this->actingAs($this->me)->from($this->editUrl())->put($this->updateUrl(), ['body' => $body])
                ->assertRedirect($this->editUrl())
                ->assertSessionHasErrors('body');
        }
        $this->assertSame('元の回答', $this->answer->refresh()->body);

        $this->actingAs($this->me)->put($this->updateUrl(), ['body' => str_repeat('あ', 2000)])->assertSessionHasNoErrors();
    }

    public function test_other_users_including_question_owner_get_403(): void
    {
        foreach ([$this->asker, User::factory()->create()] as $user) {
            $this->actingAs($user)->get($this->editUrl())->assertForbidden();
            $this->actingAs($user)->put($this->updateUrl(), ['body' => '書き換え'])->assertForbidden();
            $this->actingAs($user)->delete($this->destroyUrl())->assertForbidden();
        }

        $this->assertSame('元の回答', $this->answer->refresh()->body);
    }

    public function test_best_answer_cannot_be_edited_or_deleted(): void
    {
        $this->assertTrue(app(ChooseBestAnswer::class)->handle($this->question, $this->answer, 5));

        $this->actingAs($this->me)->get($this->editUrl())->assertForbidden();
        $this->actingAs($this->me)->put($this->updateUrl(), ['body' => '書き換え'])->assertForbidden();
        $this->actingAs($this->me)->delete($this->destroyUrl())->assertForbidden();

        $this->assertModelExists($this->answer);
        $this->assertSame('元の回答', $this->answer->refresh()->body);

        $this->actingAs($this->me)->get(route('questions.show', $this->question))
            ->assertOk()
            ->assertDontSee($this->editUrl())
            ->assertDontSee('delete-sheet-answer-'.$this->answer->id);
    }

    public function test_hidden_answer_and_answer_on_hidden_question_are_404(): void
    {
        $hiddenAnswer = Answer::factory()->for($this->question)->for($this->me)->hidden()->create();
        $onHidden = Answer::factory()->for(Question::factory()->hidden())->for($this->me)->create();

        foreach ([$hiddenAnswer, $onHidden] as $answer) {
            $this->actingAs($this->me)->get($this->editUrl($answer))->assertNotFound();
            $this->actingAs($this->me)->put($this->updateUrl($answer), ['body' => '書き換え'])->assertNotFound();
            $this->actingAs($this->me)->delete($this->destroyUrl($answer))->assertNotFound();
            $this->assertModelExists($answer);
        }
    }

    public function test_answer_of_another_question_in_url_is_404(): void
    {
        $otherQuestion = Question::factory()->create();

        $this->actingAs($this->me)->get(route('answers.edit', [$otherQuestion, $this->answer]))->assertNotFound();
        $this->actingAs($this->me)->delete(route('answers.destroy', [$otherQuestion, $this->answer]))->assertNotFound();
        $this->assertModelExists($this->answer);
    }

    public function test_deleting_answer_removes_its_reports_and_lowers_answer_count(): void
    {
        Answer::factory()->for($this->me)->create();
        Report::factory()->forAnswer($this->answer)->create();
        $questionReport = Report::factory()->create(['question_id' => $this->question->id]);
        Like::factory()->forAnswer($this->answer)->create();
        $questionLike = Like::factory()->create(['question_id' => $this->question->id]);
        $this->assertSame(2, app(RankingService::class)->answerCount($this->me));

        $this->actingAs($this->me)->delete($this->destroyUrl())
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHas('success', '回答を削除しました');

        $this->assertModelMissing($this->answer);
        $this->assertModelExists($this->question);
        $this->assertSame([$questionReport->id], Report::pluck('id')->all());
        $this->assertSame([$questionLike->id], Like::pluck('id')->all(), '回答へのいいねだけが削除される');
        $this->assertSame(1, app(RankingService::class)->answerCount($this->me));
    }

    public function test_resubmitting_delete_redirects_to_question_or_home_instead_of_404(): void
    {
        $url = $this->destroyUrl();
        $this->actingAs($this->me)->delete($url);

        $this->actingAs($this->me)->delete($url)
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHas('error', '既に削除されています');

        // 質問ごと削除されていれば G4
        $this->question->delete();
        $this->actingAs($this->me)->delete($url)
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', '既に削除されています');
    }

    public function test_answer_chosen_as_best_after_page_load_is_not_changed(): void
    {
        // 権限の確認のあとにベストアンサーに決まった場合（ロックの中で確かめ直す）
        $this->answer->is_best = true;
        $this->answer->rating = 4;
        $this->answer->saveQuietly();
        $this->answer->is_best = false; // 権限の確認時点では未決定だった、という状態を再現する

        $controller = app(AnswerController::class);
        $response = $controller->destroy($this->question, $this->answer);

        $this->assertSame(route('questions.show', $this->question), $response->getTargetUrl());
        $this->assertSame('ベストアンサーに決定済みの回答は編集・削除できません', session('error'));
        $this->assertModelExists($this->answer);
    }

    public function test_show_page_has_edit_and_delete_only_on_own_answers(): void
    {
        $others = Answer::factory()->for($this->question)->create(['body' => '他人の回答']);

        $page = $this->actingAs($this->me)->get(route('questions.show', $this->question))->assertOk();
        $page->assertSee('href="'.$this->editUrl().'"', false)
            ->assertSee('id="delete-sheet-answer-'.$this->answer->id.'"', false)
            ->assertSeeText('回答を削除しますか？')
            ->assertDontSee($this->editUrl($others))
            ->assertDontSee('delete-sheet-answer-'.$others->id)
            ->assertDontSee('delete-sheet-question');
    }
}
