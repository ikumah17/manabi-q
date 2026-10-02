<?php

namespace Tests\Feature\Questions;

use App\Models\AiAnswer;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Stamp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G7 質問詳細
 */
class QuestionShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_shows_question_ai_answer_best_answer_and_other_answers_in_order(): void
    {
        $author = User::factory()->create(['name' => '山田 太郎']);
        $question = Question::factory()->for($author)->withAi()->resolved()
            ->create(['title' => '固有値の質問', 'body' => '質問の本文']);
        AiAnswer::factory()->for($question)->create(['body' => 'AIの回答本文']);
        $stamp = Stamp::factory()->requiring(50)->create();
        Answer::factory()->for($question)->best(4)
            ->for(User::factory()->state(['name' => '経済 一郎']))
            ->create(['body' => 'ベストの回答', 'stamp_id' => $stamp->id, 'created_at' => now()->subHours(3)]);
        Answer::factory()->for($question)->create(['body' => '先の回答', 'created_at' => now()->subHours(2)]);
        Answer::factory()->for($question)->create(['body' => '後の回答', 'created_at' => now()->subHour()]);
        Answer::factory()->for($question)->hidden()->create(['body' => '非表示の回答']);

        $this->actingAs($author)->get(route('questions.show', $question))
            ->assertOk()
            ->assertSeeTextInOrder([
                '解決済み', '固有値の質問', '山田 太郎（あなた）', '回答 3件', '質問の本文',
                'AIの即時回答', 'AIの回答本文',
                'ベストアンサー', '★★★★☆', '経済 一郎', 'ベストの回答', '★ 50回答達成',
                '回答一覧（2件）', '先の回答', '後の回答',
            ])
            ->assertDontSee('非表示の回答')
            ->assertSee(route('answers.create', $question))
            ->assertSee(route('home'));
    }

    public function test_other_users_do_not_see_you_label(): void
    {
        $question = Question::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('questions.show', $question))
            ->assertDontSee('（あなた）');
    }

    public function test_choose_best_button_only_for_author_of_unresolved_question_and_not_own_answer(): void
    {
        $author = User::factory()->create();
        $question = Question::factory()->for($author)->create();
        $others = Answer::factory()->for($question)->create();
        $own = Answer::factory()->for($question)->for($author)->create();

        $this->actingAs($author)->get(route('questions.show', $question))
            ->assertSee('ベストアンサーに選ぶ')
            ->assertSee('id="best-sheet-'.$others->id.'"', false)
            ->assertDontSee('id="best-sheet-'.$own->id.'"', false);

        // 質問者以外には表示しない
        $this->actingAs(User::factory()->create())->get(route('questions.show', $question))
            ->assertDontSee('ベストアンサーに選ぶ');
    }

    public function test_choose_best_button_is_hidden_after_resolution(): void
    {
        $author = User::factory()->create();
        $question = Question::factory()->for($author)->resolved()->create();
        Answer::factory()->for($question)->best()->create();
        Answer::factory()->for($question)->create();

        $this->actingAs($author)->get(route('questions.show', $question))
            ->assertDontSee('ベストアンサーに選ぶ');
    }

    public function test_no_answers_message(): void
    {
        $question = Question::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('questions.show', $question))
            ->assertSee('回答一覧（0件）')
            ->assertSee('まだ回答はありません');
    }

    public function test_hidden_question_is_not_found_even_for_author(): void
    {
        $author = User::factory()->create();
        $question = Question::factory()->for($author)->hidden()->create();

        $this->actingAs($author)->get(route('questions.show', $question))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('questions.show', $question))->assertNotFound();
    }

    public function test_body_line_breaks_are_kept_and_html_is_escaped(): void
    {
        $question = Question::factory()->create(['body' => "1行目\n<b>2行目</b>"]);

        $this->actingAs(User::factory()->create())->get(route('questions.show', $question))
            ->assertSee("1行目\n&lt;b&gt;2行目&lt;/b&gt;", false);
    }

    public function test_guest_and_admin_cannot_open_detail(): void
    {
        $question = Question::factory()->create();

        $this->get(route('questions.show', $question))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('questions.show', $question))
            ->assertRedirect(route('admin.dashboard'));
    }
}
