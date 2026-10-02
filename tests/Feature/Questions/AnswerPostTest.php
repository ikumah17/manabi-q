<?php

namespace Tests\Feature\Questions;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Stamp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G8 回答投稿（スタンプは獲得済みのもののみ）
 */
class AnswerPostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Question $question;

    private Stamp $stamp10;

    private Stamp $stamp50;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->user = User::factory()->create();
        $this->question = Question::factory()->create(['title' => '回答対象の質問']);
        $this->stamp10 = Stamp::factory()->requiring(10)->create();
        $this->stamp50 = Stamp::factory()->requiring(50)->create();
    }

    private function giveAnswers(int $count, bool $hidden = false): void
    {
        $factory = Answer::factory()->for($this->user)->count($count);
        ($hidden ? $factory->hidden() : $factory)->create();
    }

    public function test_form_shows_question_title_and_locked_stamps(): void
    {
        $this->actingAs($this->user)->get(route('answers.create', $this->question))
            ->assertOk()
            ->assertSeeInOrder(['回答する質問', '回答対象の質問', '回答本文（必須）', 'スタンプ（任意・獲得済みのみ選択可）', 'なし'])
            ->assertSee('10回答達成（未獲得）')
            ->assertSee('50回答達成（未獲得）')
            ->assertSee(route('questions.show', $this->question));
    }

    public function test_none_stamp_stays_selected_after_validation_error(): void
    {
        $this->actingAs($this->user)
            ->from(route('answers.create', $this->question))
            ->post(route('answers.store', $this->question), ['body' => '', 'stamp_id' => ''])
            ->assertSessionHasErrors('body');

        $this->actingAs($this->user)->get(route('answers.create', $this->question))
            ->assertSee('value="" class="form-check-input" checked', false);
    }

    public function test_answer_is_saved_and_returns_to_detail_with_message(): void
    {
        $this->actingAs($this->user)
            ->post(route('answers.store', $this->question), ['body' => '回答です'])
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHas('success', '回答を投稿しました');

        $answer = Answer::where('question_id', $this->question->id)->firstOrFail();
        $this->assertSame($this->user->id, $answer->user_id);
        $this->assertSame('回答です', $answer->body);
        $this->assertNull($answer->stamp_id);
        $this->assertFalse($answer->is_best);
        $this->assertNull($answer->rating);
    }

    public function test_best_flag_and_rating_cannot_be_set_from_form(): void
    {
        $this->actingAs($this->user)->post(route('answers.store', $this->question), [
            'body' => '回答', 'is_best' => '1', 'rating' => '5', 'status' => '0',
        ]);

        $answer = Answer::where('question_id', $this->question->id)->firstOrFail();
        $this->assertFalse($answer->is_best);
        $this->assertNull($answer->rating);
        $this->assertTrue($answer->isPublished());
    }

    public function test_validation_messages(): void
    {
        $this->actingAs($this->user)->post(route('answers.store', $this->question), ['body' => ''])
            ->assertSessionHasErrors(['body' => '回答本文を入力してください']);

        $this->actingAs($this->user)->post(route('answers.store', $this->question), ['body' => Str::repeat('あ', 2001)])
            ->assertSessionHasErrors(['body' => '回答本文は2000文字以内で入力してください']);

        $this->actingAs($this->user)->post(route('answers.store', $this->question), ['body' => Str::repeat('あ', 2000)])
            ->assertSessionHasNoErrors();
    }

    public function test_unearned_stamp_is_rejected_at_nine_answers(): void
    {
        $this->giveAnswers(9);

        $this->actingAs($this->user)
            ->post(route('answers.store', $this->question), ['body' => '回答', 'stamp_id' => $this->stamp10->id])
            ->assertSessionHasErrors(['stamp_id' => '獲得していないスタンプは選択できません']);
    }

    public function test_stamp_is_earned_at_ten_answers_but_not_the_next_level(): void
    {
        $this->giveAnswers(10);

        $this->actingAs($this->user)->get(route('answers.create', $this->question))
            ->assertDontSee('10回答達成（未獲得）')
            ->assertSee('50回答達成（未獲得）');

        $this->actingAs($this->user)
            ->post(route('answers.store', $this->question), ['body' => '回答', 'stamp_id' => $this->stamp10->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($this->stamp10->id, Answer::where('question_id', $this->question->id)->firstOrFail()->stamp_id);

        $this->actingAs($this->user)
            ->post(route('answers.store', $this->question), ['body' => '回答2', 'stamp_id' => $this->stamp50->id])
            ->assertSessionHasErrors('stamp_id');
    }

    public function test_hidden_answers_do_not_count_for_stamps(): void
    {
        $this->giveAnswers(9);
        $this->giveAnswers(5, hidden: true);

        $this->actingAs($this->user)
            ->post(route('answers.store', $this->question), ['body' => '回答', 'stamp_id' => $this->stamp10->id])
            ->assertSessionHasErrors('stamp_id');
    }

    public function test_unknown_stamp_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post(route('answers.store', $this->question), ['body' => '回答', 'stamp_id' => 9999])
            ->assertSessionHasErrors(['stamp_id' => '選択されたスタンプは正しくありません']);
    }

    public function test_can_answer_own_and_resolved_questions(): void
    {
        $own = Question::factory()->for($this->user)->create();
        $resolved = Question::factory()->resolved()->create();

        $this->actingAs($this->user)->post(route('answers.store', $own), ['body' => '自分の質問への回答'])
            ->assertRedirect(route('questions.show', $own));
        $this->actingAs($this->user)->post(route('answers.store', $resolved), ['body' => '解決済みへの回答'])
            ->assertRedirect(route('questions.show', $resolved));
    }

    public function test_cannot_answer_hidden_question(): void
    {
        $hidden = Question::factory()->hidden()->create();

        $this->actingAs($this->user)->get(route('answers.create', $hidden))->assertNotFound();
        $this->actingAs($this->user)->post(route('answers.store', $hidden), ['body' => '回答'])->assertNotFound();
        $this->assertSame(0, Answer::where('question_id', $hidden->id)->count());
    }
}
