<?php

namespace Tests\Feature\Questions;

use App\Models\AiAnswer;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Like;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use App\Services\Ranking\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * G19 質問編集・G21 質問の削除（本人のみ）
 */
class QuestionEditTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->me = User::factory()->create();
        $this->question = Question::factory()->for($this->me)->withAi()->create(['title' => '元のタイトル', 'body' => '元の本文']);
    }

    /**
     * @return array<string, mixed>
     */
    private function input(array $overrides = []): array
    {
        return [
            'title' => '直したタイトル',
            'category_id' => Category::firstOrCreate(['name' => '新しいカテゴリ'])->id,
            'body' => '直した本文',
            ...$overrides,
        ];
    }

    public function test_owner_sees_edit_form_with_current_values_and_without_ai_option(): void
    {
        $this->actingAs($this->me)->get(route('questions.edit', $this->question))
            ->assertOk()
            ->assertSeeText('質問を編集')
            ->assertSee('value="元のタイトル"', false)
            ->assertSeeText('元の本文')
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSeeText('更新する')
            ->assertDontSee('name="use_ai"', false);
    }

    public function test_owner_updates_title_category_and_body_only_without_regenerating_ai(): void
    {
        Http::fake();
        $aiAnswer = AiAnswer::factory()->for($this->question)->create(['body' => '元のAI回答']);
        $input = $this->input(['use_ai' => '0']);

        $this->actingAs($this->me)->put(route('questions.update', $this->question), $input)
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHas('success', '質問を更新しました');

        $question = $this->question->refresh();
        $this->assertSame(['直したタイトル', $input['category_id'], '直した本文'], [$question->title, $question->category_id, $question->body]);
        $this->assertTrue($question->use_ai, 'AI回答希望は変更しない');
        $this->assertSame('元のAI回答', $aiAnswer->refresh()->body, 'AI回答は作り直さない');
        Http::assertNothingSent();
    }

    public function test_resolved_question_can_be_edited(): void
    {
        $question = Question::factory()->for($this->me)->resolved()->create();

        $this->actingAs($this->me)->put(route('questions.update', $question), $this->input())
            ->assertSessionHas('success', '質問を更新しました');
        $this->assertTrue($question->refresh()->is_resolved);
    }

    public function test_validation_errors_keep_the_question_unchanged(): void
    {
        $cases = [
            ['title' => ''],
            ['title' => str_repeat('あ', 101)],
            ['body' => ''],
            ['body' => str_repeat('あ', 2001)],
            ['category_id' => ''],
            ['category_id' => 9999],
        ];

        foreach ($cases as $overrides) {
            $this->actingAs($this->me)
                ->from(route('questions.edit', $this->question))
                ->put(route('questions.update', $this->question), $this->input($overrides))
                ->assertRedirect(route('questions.edit', $this->question))
                ->assertSessionHasErrors(array_keys($overrides));
        }

        $this->assertSame(['元のタイトル', '元の本文'], [$this->question->refresh()->title, $this->question->body]);

        // 境界値（100文字・2000文字）は通る
        $this->actingAs($this->me)->put(route('questions.update', $this->question), $this->input([
            'title' => str_repeat('あ', 100), 'body' => str_repeat('あ', 2000),
        ]))->assertSessionHasNoErrors();
    }

    public function test_other_users_get_403_for_edit_update_and_delete(): void
    {
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('questions.edit', $this->question))->assertForbidden();
        $this->actingAs($other)->put(route('questions.update', $this->question), $this->input())->assertForbidden();
        $this->actingAs($other)->delete(route('questions.destroy', $this->question))->assertForbidden();

        $this->assertModelExists($this->question);
        $this->assertSame('元のタイトル', $this->question->refresh()->title);
    }

    public function test_hidden_question_is_404_even_for_owner(): void
    {
        $hidden = Question::factory()->for($this->me)->hidden()->create();

        $this->actingAs($this->me)->get(route('questions.edit', $hidden))->assertNotFound();
        $this->actingAs($this->me)->put(route('questions.update', $hidden), $this->input())->assertNotFound();
        $this->actingAs($this->me)->delete(route('questions.destroy', $hidden))->assertNotFound();
        $this->assertModelExists($hidden);
    }

    public function test_deleting_question_cascades_to_answers_ai_answer_and_reports(): void
    {
        $answerer = User::factory()->create();
        AiAnswer::factory()->for($this->question)->create();
        $answers = Answer::factory()->for($this->question)->for($answerer)->count(2)->create();
        Answer::factory()->for($this->question)->best(4)->create();
        Report::factory()->create(['question_id' => $this->question->id]);
        Report::factory()->forAnswer($answers[0])->create();
        $otherAnswer = Answer::factory()->for($answerer)->create();
        $otherReport = Report::factory()->forAnswer($otherAnswer)->create();
        Like::factory()->create(['question_id' => $this->question->id]);
        Like::factory()->forAnswer($answers[1])->create();
        $otherLike = Like::factory()->forAnswer($otherAnswer)->create();
        $this->assertSame(3, app(RankingService::class)->answerCount($answerer));

        $this->actingAs($this->me)->delete(route('questions.destroy', $this->question))
            ->assertRedirect(route('home'))
            ->assertSessionHas('success', '質問を削除しました');

        $this->assertModelMissing($this->question);
        $this->assertSame(0, Answer::where('question_id', $this->question->id)->count());
        $this->assertSame(0, AiAnswer::where('question_id', $this->question->id)->count());
        $this->assertSame([$otherReport->id], Report::pluck('id')->all());
        $this->assertSame([$otherLike->id], Like::pluck('id')->all(), '質問と、その回答へのいいねも削除される');
        $this->assertModelExists($otherAnswer);
        $this->assertSame(1, app(RankingService::class)->answerCount($answerer), '回答数・ランキングは下がる');
    }

    public function test_resolved_question_with_answers_can_be_deleted(): void
    {
        $question = Question::factory()->for($this->me)->resolved()->create();
        Answer::factory()->for($question)->best(5)->create();

        $this->actingAs($this->me)->delete(route('questions.destroy', $question))
            ->assertSessionHas('success', '質問を削除しました');
        $this->assertModelMissing($question);
    }

    public function test_resubmitting_delete_redirects_home_with_message_instead_of_404(): void
    {
        $this->actingAs($this->me)->delete(route('questions.destroy', $this->question));

        $this->actingAs($this->me)->delete(route('questions.destroy', $this->question))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', '既に削除されています');

        $this->actingAs($this->me)->followingRedirects()
            ->delete(route('questions.destroy', $this->question))
            ->assertOk()
            ->assertSeeText('既に削除されています');
    }

    public function test_show_page_has_edit_and_delete_only_for_owner(): void
    {
        $this->actingAs($this->me)->get(route('questions.show', $this->question))
            ->assertOk()
            ->assertSee('href="'.route('questions.edit', $this->question).'"', false)
            ->assertSee('data-bs-target="#delete-sheet-question"', false)
            ->assertSee('id="delete-sheet-question"', false)
            ->assertSeeText('質問を削除しますか？')
            ->assertSee('action="'.route('questions.destroy', $this->question).'"', false);

        $this->actingAs(User::factory()->create())->get(route('questions.show', $this->question))
            ->assertOk()
            ->assertDontSee(route('questions.edit', $this->question))
            ->assertDontSee('delete-sheet-question')
            ->assertSee('data-bs-target="#report-sheet-question"', false);
    }

    public function test_admin_cannot_use_general_edit_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('questions.edit', $this->question))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->delete(route('questions.destroy', $this->question));
        $this->assertModelExists($this->question);
    }
}
