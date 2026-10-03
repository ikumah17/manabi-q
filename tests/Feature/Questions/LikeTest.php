<?php

namespace Tests\Feature\Questions;

use App\Models\Answer;
use App\Models\Like;
use App\Models\Question;
use App\Models\User;
use App\Services\Ranking\RankingService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * G7 ⑩ いいね（質問・回答）と、G4 ⑦ のいいね数
 */
class LikeTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private User $author;

    private Question $question;

    private Answer $answer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->me = User::factory()->create();
        $this->author = User::factory()->create();
        $this->question = Question::factory()->for($this->author)->create(['title' => 'いいねされる質問']);
        $this->answer = Answer::factory()->for($this->question)->for($this->author)->create();
    }

    private function questionUrl(?Question $question = null): string
    {
        return route('likes.question.store', $question ?? $this->question);
    }

    private function answerUrl(?Answer $answer = null): string
    {
        $answer ??= $this->answer;

        return route('likes.answer.store', [$answer->question_id, $answer]);
    }

    public function test_like_and_unlike_question(): void
    {
        $this->actingAs($this->me)->post($this->questionUrl())
            ->assertRedirect(route('questions.show', $this->question));
        $this->assertSame(1, $this->question->likes()->where('user_id', $this->me->id)->count());

        $this->actingAs($this->me)->get(route('questions.show', $this->question))
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('data-icon="heart-fill"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSeeText('いいね済み 1');

        $this->actingAs($this->me)->delete($this->questionUrl())
            ->assertRedirect(route('questions.show', $this->question));
        $this->assertSame(0, Like::count());

        $this->actingAs($this->me)->get(route('questions.show', $this->question))
            ->assertSee('aria-pressed="false"', false)
            ->assertDontSee('data-icon="heart-fill"', false)
            ->assertSeeText('いいね 0')
            ->assertDontSeeText('いいね済み');
    }

    public function test_like_and_unlike_answer_including_best_answer(): void
    {
        $this->actingAs($this->me)->post($this->answerUrl())
            ->assertRedirect(route('questions.show', $this->question).'#answer-'.$this->answer->id);
        $this->assertSame(1, $this->answer->likes()->count());

        $best = Answer::factory()->for($this->question)->best(5)->create();
        $this->actingAs($this->me)->post($this->answerUrl($best));
        $this->assertSame(1, $best->likes()->count());

        $this->actingAs($this->me)->delete($this->answerUrl())
            ->assertRedirect(route('questions.show', $this->question).'#answer-'.$this->answer->id);
        $this->assertSame([$best->id], Like::pluck('answer_id')->all());
        $this->assertNull(Like::first()->question_id, '回答へのいいねは question_id を持たない');
    }

    public function test_pressing_twice_keeps_one_like_and_unlike_twice_is_harmless(): void
    {
        $this->actingAs($this->me)->post($this->questionUrl());
        $this->actingAs($this->me)->post($this->questionUrl())->assertRedirect(route('questions.show', $this->question));
        $this->assertSame(1, Like::count());

        $this->actingAs($this->me)->delete($this->questionUrl());
        $this->actingAs($this->me)->delete($this->questionUrl())->assertRedirect(route('questions.show', $this->question));
        $this->assertSame(0, Like::count());
    }

    public function test_simultaneous_like_hitting_unique_constraint_is_treated_as_success(): void
    {
        // 確認（SELECT）と登録（INSERT）の間に、もう一方のリクエストが同じいいねを登録した状態を再現する
        Like::creating(function (Like $like) {
            DB::table('likes')->insert([
                'user_id' => $like->user_id, 'question_id' => $like->question_id, 'answer_id' => $like->answer_id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        $this->actingAs($this->me)->post($this->questionUrl())
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Like::count());
    }

    public function test_unique_constraints_allow_one_like_per_user_and_target(): void
    {
        Like::factory()->for($this->me)->create(['question_id' => $this->question->id]);
        Like::factory()->for($this->me)->forAnswer($this->answer)->create(); // 質問と回答のいいねは衝突しない
        Like::factory()->create(['question_id' => $this->question->id]);     // 別のユーザー

        $this->expectException(UniqueConstraintViolationException::class);
        Like::factory()->for($this->me)->forAnswer($this->answer)->create();
    }

    public function test_own_posts_are_403_and_show_count_only(): void
    {
        $this->actingAs($this->author)->post($this->questionUrl())->assertForbidden();
        $this->actingAs($this->author)->delete($this->questionUrl())->assertForbidden();
        $this->actingAs($this->author)->post($this->answerUrl())->assertForbidden();
        $this->actingAs($this->author)->delete($this->answerUrl())->assertForbidden();
        $this->assertSame(0, Like::count());

        Like::factory()->count(2)->create(['question_id' => $this->question->id]);
        Like::factory()->forAnswer($this->answer)->create();

        $this->actingAs($this->author)->get(route('questions.show', $this->question))
            ->assertOk()
            ->assertSeeTextInOrder(['いいねされる質問', 'いいね 2', '回答一覧', 'いいね 1'])
            ->assertSee('data-like-count', false)
            ->assertDontSee('data-like-button', false)
            ->assertDontSee($this->questionUrl())
            ->assertDontSee($this->answerUrl());
    }

    public function test_hidden_posts_are_404(): void
    {
        $hiddenQuestion = Question::factory()->hidden()->create();
        $hiddenAnswer = Answer::factory()->for($this->question)->hidden()->create();
        $onHidden = Answer::factory()->for($hiddenQuestion)->create();

        $this->actingAs($this->me)->post($this->questionUrl($hiddenQuestion))->assertNotFound();
        $this->actingAs($this->me)->delete($this->questionUrl($hiddenQuestion))->assertNotFound();
        foreach ([$hiddenAnswer, $onHidden] as $answer) {
            $this->actingAs($this->me)->post($this->answerUrl($answer))->assertNotFound();
            $this->actingAs($this->me)->delete($this->answerUrl($answer))->assertNotFound();
        }
        // 別の質問の回答を指定した URL
        $this->actingAs($this->me)->post(route('likes.answer.store', [Question::factory()->create(), $this->answer]))->assertNotFound();

        $this->assertSame(0, Like::count());
    }

    public function test_suspended_users_cannot_like_but_their_likes_are_counted(): void
    {
        $suspended = User::factory()->suspended()->create();
        Like::factory()->for($suspended)->create(['question_id' => $this->question->id]);

        $this->actingAs($suspended)->post($this->questionUrl())->assertRedirect(route('login'));
        $this->assertSame(1, Like::count());

        $this->actingAs($this->me)->get(route('questions.show', $this->question))->assertSeeText('いいね 1');
    }

    public function test_admin_is_redirected_to_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post($this->questionUrl())
            ->assertRedirect(route('admin.dashboard'));
        $this->assertSame(0, Like::count());
    }

    public function test_home_card_shows_like_count_only_and_mypage_does_not(): void
    {
        Like::factory()->count(3)->create(['question_id' => $this->question->id]);
        Like::factory()->forAnswer($this->answer)->count(2)->create(); // 回答へのいいねは質問の件数に含めない

        $this->actingAs($this->me)->get(route('home'))
            ->assertOk()
            ->assertSeeTextInOrder(['いいねされる質問', '回答 1', 'いいね 3'])
            ->assertDontSee('data-like-button', false);

        $this->actingAs($this->author)->get(route('mypage'))
            ->assertOk()
            ->assertSeeText('いいねされる質問')
            ->assertDontSeeText('いいね 3');
    }

    public function test_likes_do_not_affect_ranking(): void
    {
        $before = app(RankingService::class)->forUser($this->author);
        Like::factory()->forAnswer($this->answer)->count(5)->create();
        Like::factory()->count(5)->create(['question_id' => $this->question->id]);
        $after = app(RankingService::class)->forUser($this->author);

        $this->assertEquals($before, $after);
    }

    public function test_question_page_and_home_query_counts_do_not_grow_with_answers_and_likes(): void
    {
        $count = function (string $url): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($this->me)->get($url)->assertOk();
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };
        $show = route('questions.show', $this->question);
        $home = route('home');
        $before = [$count($show), $count($home)];

        foreach (range(1, 6) as $i) {
            $answer = Answer::factory()->for($this->question)->create();
            Like::factory()->forAnswer($answer)->count(2)->create();
            Like::factory()->create(['question_id' => Question::factory()->create()->id]);
        }

        $this->assertSame($before, [$count($show), $count($home)]);
    }
}
