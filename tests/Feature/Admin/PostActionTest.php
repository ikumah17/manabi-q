<?php

namespace Tests\Feature\Admin;

use App\Enums\PostStatus;
use App\Models\AiAnswer;
use App\Models\Answer;
use App\Models\Like;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use App\Services\Ranking\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G14 投稿管理の操作（公開／非表示の切り替え・削除と連動の範囲）
 */
class PostActionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function act(string $method, string $route, int $id, array $query = [])
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.posts.index', $query))
            ->{$method}(route($route, $id));
    }

    public function test_hide_and_publish_question_is_idempotent_and_changes_visibility(): void
    {
        $question = Question::factory()->create(['title' => '切り替える質問']);
        $report = Report::factory()->create(['question_id' => $question->id]);
        $viewer = User::factory()->create();

        $this->act('post', 'admin.posts.questions.hide', $question->id, ['type' => 'question'])
            ->assertRedirect(route('admin.posts.index', ['type' => 'question']))
            ->assertSessionHas('success', '非表示にしました');
        $this->act('post', 'admin.posts.questions.hide', $question->id)->assertSessionHas('success'); // 二重送信しても非表示のまま
        $this->assertSame(PostStatus::Hidden, $question->refresh()->status);
        $this->actingAs($viewer)->get(route('questions.show', $question))->assertNotFound();
        $this->assertSame(0, $report->refresh()->status->value, 'G14 の切り替えでは通報の状態を変えない');

        $this->act('post', 'admin.posts.questions.publish', $question->id)->assertSessionHas('success', '公開にしました');
        $this->assertTrue($question->refresh()->isPublished());
        $this->actingAs($viewer)->get(route('questions.show', $question))->assertOk();
    }

    public function test_hide_and_publish_answer(): void
    {
        $answer = Answer::factory()->create(['body' => '切り替える回答']);

        $this->act('post', 'admin.posts.answers.hide', $answer->id)->assertSessionHas('success', '非表示にしました');
        $this->assertSame(PostStatus::Hidden, $answer->refresh()->status);
        $this->actingAs(User::factory()->create())->get(route('questions.show', $answer->question_id))->assertDontSee('切り替える回答');

        $this->act('post', 'admin.posts.answers.publish', $answer->id)->assertSessionHas('success', '公開にしました');
        $this->assertTrue($answer->refresh()->isPublished());
    }

    public function test_deleting_question_cascades_to_its_answers_ai_answer_and_reports_only(): void
    {
        $question = Question::factory()->withAi()->create();
        AiAnswer::factory()->for($question)->create();
        $answers = Answer::factory()->for($question)->count(2)->create();
        Report::factory()->create(['question_id' => $question->id]);
        Report::factory()->forAnswer($answers[0])->create();
        $other = Question::factory()->create();
        $otherAnswer = Answer::factory()->for($other)->create();
        $otherReport = Report::factory()->forAnswer($otherAnswer)->create();
        Like::factory()->create(['question_id' => $question->id]);
        Like::factory()->forAnswer($answers[1])->create();
        $otherLike = Like::factory()->forAnswer($otherAnswer)->create();

        $this->act('delete', 'admin.posts.questions.destroy', $question->id)
            ->assertRedirect(route('admin.posts.index'))
            ->assertSessionHas('success', '削除しました');

        $this->assertModelMissing($question);
        $this->assertSame(0, Answer::where('question_id', $question->id)->count());
        $this->assertSame(0, AiAnswer::where('question_id', $question->id)->count());
        $this->assertSame([$otherReport->id], Report::pluck('id')->all());
        $this->assertSame([$otherLike->id], Like::pluck('id')->all(), '質問と、その回答へのいいねも連動して削除される');
        $this->assertModelExists($other);
        $this->assertModelExists($otherAnswer);
    }

    public function test_deleting_answer_removes_only_its_reports_and_changes_ranking_count(): void
    {
        $answerer = User::factory()->create();
        $question = Question::factory()->create();
        $answer = Answer::factory()->for($question)->for($answerer)->create();
        Answer::factory()->for($answerer)->create();
        Report::factory()->forAnswer($answer)->create();
        $questionReport = Report::factory()->create(['question_id' => $question->id]);
        Like::factory()->forAnswer($answer)->count(2)->create();
        $questionLike = Like::factory()->create(['question_id' => $question->id]);
        $this->assertSame(2, app(RankingService::class)->answerCount($answerer));

        $this->act('delete', 'admin.posts.answers.destroy', $answer->id)->assertSessionHas('success', '削除しました');

        $this->assertModelMissing($answer);
        $this->assertModelExists($question);
        $this->assertSame([$questionReport->id], Report::pluck('id')->all());
        $this->assertSame([$questionLike->id], Like::pluck('id')->all(), '回答へのいいねだけが削除される');
        $this->assertSame(1, app(RankingService::class)->answerCount($answerer));
    }

    /**
     * J2：ベストアンサーを削除しても質問は解決済みのまま（Q30）。G7 の表示を固定する
     */
    public function test_deleting_best_answer_keeps_question_resolved_and_g7_shows_no_best_answer(): void
    {
        $author = User::factory()->create();
        $question = Question::factory()->for($author)->resolved()->create(['title' => '解決済みの質問']);
        $best = Answer::factory()->for($question)->best(5)->create(['body' => 'ベストの回答']);
        Answer::factory()->for($question)->create(['body' => '残る回答']);

        $this->act('delete', 'admin.posts.answers.destroy', $best->id)->assertSessionHas('success');

        $this->assertTrue($question->refresh()->is_resolved);
        $html = $this->actingAs($author)->get(route('questions.show', $question))
            ->assertOk()
            ->assertSeeTextInOrder(['解決済み', '解決済みの質問', '回答 1件', '回答一覧（1件）', '残る回答'])
            ->assertDontSee('ベストの回答')
            ->assertDontSee('ベストアンサーに選ぶ') // 解決済みのため選べない
            ->getContent();
        $this->assertStringNotContainsString('mq-best-answer', $html);
        $this->assertStringContainsString(route('answers.create', $question), $html); // 回答はできる
    }

    public function test_second_delete_shows_already_deleted_message(): void
    {
        $question = Question::factory()->create();

        $this->act('delete', 'admin.posts.questions.destroy', $question->id)->assertSessionHas('success');
        $this->act('delete', 'admin.posts.questions.destroy', $question->id, ['page' => 1])
            ->assertRedirect(route('admin.posts.index', ['page' => 1]))
            ->assertSessionHas('error', '既に削除されています');
        $this->act('post', 'admin.posts.answers.hide', 99999)->assertSessionHas('error', '既に削除されています');
    }

    public function test_list_has_toggle_buttons_and_delete_modal_with_escaped_message(): void
    {
        $question = Question::factory()->create(['title' => '"><script>alert(1)</script>']);
        $hidden = Answer::factory()->hidden()->create();

        $html = $this->actingAs($this->admin)->get(route('admin.posts.index'))->getContent();

        $this->assertStringContainsString(route('admin.posts.questions.hide', $question), $html);
        $this->assertStringContainsString(route('admin.posts.answers.publish', $hidden), $html);
        $this->assertStringContainsString('data-confirm-action="'.route('admin.posts.questions.destroy', $question).'"', $html);
        $this->assertStringContainsString('id="delete-post-modal"', $html);
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringContainsString('data-confirm-message="質問：&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
    }

    public function test_general_user_gets_403_and_nothing_changes(): void
    {
        $question = Question::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.posts.questions.hide', $question))->assertForbidden();
        $this->actingAs($user)->delete(route('admin.posts.questions.destroy', $question))->assertForbidden();

        $this->assertTrue($question->refresh()->isPublished());
    }
}
