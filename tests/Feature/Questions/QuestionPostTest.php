<?php

namespace Tests\Feature\Questions;

use App\Models\AiAnswer;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G5 質問投稿・G6 質問投稿完了
 */
class QuestionPostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['name' => '授業']);
    }

    public function test_form_shows_fields_and_ai_option_is_on_by_default(): void
    {
        $this->actingAs($this->user)->get('/questions/create')
            ->assertOk()
            ->assertSeeInOrder(['タイトル（必須）', 'カテゴリ（必須）', '本文（必須）', 'AIの即時回答を受け取る（Gemini）', '投稿する'])
            ->assertSee('授業')
            ->assertSee('id="use_ai" name="use_ai" value="1" class="form-check-input mq-ai-option__check"
                   checked', false);
    }

    public function test_question_is_saved_and_redirects_to_complete(): void
    {
        $response = $this->actingAs($this->user)->post('/questions', [
            'title' => '固有値の求め方',
            'category_id' => $this->category->id,
            'body' => "1行目\n2行目",
            'use_ai' => '1',
        ]);

        $question = Question::firstOrFail();
        $response->assertRedirect(route('questions.complete', $question));
        $this->assertSame($this->user->id, $question->user_id);
        $this->assertTrue($question->use_ai);
        $this->assertFalse($question->is_resolved);
        $this->assertTrue($question->isPublished());
    }

    public function test_use_ai_off_is_saved_as_zero(): void
    {
        $this->actingAs($this->user)->post('/questions', [
            'title' => 'AI不要',
            'category_id' => $this->category->id,
            'body' => '本文',
            'use_ai' => '0',
        ]);

        $this->assertFalse(Question::firstOrFail()->use_ai);
    }

    public function test_status_and_resolved_cannot_be_set_from_form(): void
    {
        $this->actingAs($this->user)->post('/questions', [
            'title' => 'タイトル',
            'category_id' => $this->category->id,
            'body' => '本文',
            'is_resolved' => '1',
            'status' => '0',
            'user_id' => User::factory()->create()->id,
        ]);

        $question = Question::firstOrFail();
        $this->assertFalse($question->is_resolved);
        $this->assertTrue($question->isPublished());
        $this->assertSame($this->user->id, $question->user_id);
    }

    public function test_validation_messages_follow_design(): void
    {
        $this->actingAs($this->user)->post('/questions', ['title' => '', 'category_id' => '', 'body' => ''])
            ->assertSessionHasErrors([
                'title' => 'タイトルを入力してください',
                'category_id' => 'カテゴリを選択してください',
                'body' => '本文を入力してください',
            ]);

        $this->actingAs($this->user)->post('/questions', [
            'title' => Str::repeat('あ', 101),
            'category_id' => 9999,
            'body' => Str::repeat('あ', 2001),
        ])->assertSessionHasErrors([
            'title' => 'タイトルは100文字以内で入力してください',
            'category_id' => '選択されたカテゴリは正しくありません',
            'body' => '本文は2000文字以内で入力してください',
        ]);

        $this->assertSame(0, Question::count());
    }

    public function test_max_lengths_are_accepted(): void
    {
        $this->actingAs($this->user)->post('/questions', [
            'title' => Str::repeat('あ', 100),
            'category_id' => $this->category->id,
            'body' => Str::repeat('あ', 2000),
        ])->assertSessionHasNoErrors();
    }

    public function test_complete_screen_shows_posted_content_to_author(): void
    {
        $question = Question::factory()->for($this->user)->for($this->category)
            ->create(['title' => '投稿したタイトル', 'body' => '投稿した本文']);

        $this->actingAs($this->user)->get(route('questions.complete', $question))
            ->assertOk()
            ->assertSeeInOrder(['質問を投稿しました', '授業', '未解決', '投稿したタイトル', '投稿した本文'])
            ->assertSee(route('questions.show', $question))
            ->assertSee(route('home'))
            ->assertDontSee('AIの即時回答');
    }

    public function test_complete_screen_shows_ai_answer_with_note_when_requested(): void
    {
        $question = Question::factory()->for($this->user)->withAi()->create();
        AiAnswer::factory()->for($question)->create(['body' => 'AIからの回答です']);

        $this->actingAs($this->user)->get(route('questions.complete', $question))
            ->assertSee('AIの即時回答')
            ->assertSee('AIからの回答です')
            ->assertSee('※ AIの回答は誤りを含む場合があります。参考情報としてご利用ください。');
    }

    public function test_complete_screen_of_others_question_redirects_to_detail(): void
    {
        $question = Question::factory()->create();

        $this->actingAs($this->user)->get(route('questions.complete', $question))
            ->assertRedirect(route('questions.show', $question));
    }

    public function test_complete_screen_of_hidden_question_is_not_found(): void
    {
        $question = Question::factory()->for($this->user)->hidden()->create();

        $this->actingAs($this->user)->get(route('questions.complete', $question))->assertNotFound();
    }

    public function test_question_text_is_escaped(): void
    {
        $question = Question::factory()->for($this->user)->create(['title' => '<script>alert(1)</script>']);

        $this->actingAs($this->user)->get(route('questions.complete', $question))
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
