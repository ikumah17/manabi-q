<?php

namespace Tests\Feature\Database;

use App\Enums\PostStatus;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AiAnswer;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\Report;
use App\Models\Stamp;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_defaults_to_active_general_user_with_hashed_password(): void
    {
        $user = User::create(['name' => '山田 太郎', 'email' => 'yamada@example.com', 'password' => 'password123']);
        $user->refresh();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isActive());
        $this->assertNotSame('password123', $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_role_and_status_cannot_be_mass_assigned(): void
    {
        $user = User::create([
            'name' => '攻撃者',
            'email' => 'attacker@example.com',
            'password' => 'password123',
            'role' => 1,
            'status' => 0,
        ])->refresh();

        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_question_relations_and_casts(): void
    {
        $question = Question::factory()->withAi()->create();
        AiAnswer::factory()->for($question)->create();
        $best = Answer::factory()->for($question)->best(4)->create();
        Answer::factory()->for($question)->count(2)->create();

        $question->refresh();

        $this->assertInstanceOf(User::class, $question->user);
        $this->assertInstanceOf(Category::class, $question->category);
        $this->assertInstanceOf(AiAnswer::class, $question->aiAnswer);
        $this->assertCount(3, $question->answers);
        $this->assertTrue($question->bestAnswer->is($best));
        $this->assertSame(4, $question->bestAnswer->rating);
        $this->assertTrue($question->use_ai);
        $this->assertFalse($question->is_resolved);
        $this->assertSame(PostStatus::Published, $question->status);
    }

    public function test_published_scope_excludes_hidden_posts(): void
    {
        $visible = Question::factory()->create();
        Question::factory()->hidden()->create();
        Answer::factory()->for($visible)->create();
        Answer::factory()->for($visible)->hidden()->create();

        $this->assertSame(1, Question::published()->count());
        $this->assertSame(1, $visible->answers()->published()->count());
    }

    public function test_answer_relations_with_stamp(): void
    {
        $stamp = Stamp::factory()->requiring(10)->create();
        $answer = Answer::factory()->create(['stamp_id' => $stamp->id]);

        $this->assertTrue($answer->stamp->is($stamp));
        $this->assertSame(asset('images/stamps/stamp-10.svg'), $stamp->image_url);
        $this->assertCount(1, $answer->user->answers);
    }

    public function test_report_relations_and_enum_casts(): void
    {
        $questionReport = Report::factory()->create(['reason' => ReportReason::Spam]);
        $answerReport = Report::factory()->forAnswer()->create();

        $this->assertSame(ReportReason::Spam, $questionReport->reason);
        $this->assertSame('スパム・宣伝', $questionReport->reason->label());
        $this->assertSame(ReportStatus::Pending, $questionReport->status);
        $this->assertInstanceOf(Question::class, $questionReport->question);
        $this->assertFalse($questionReport->isAboutAnswer());

        $this->assertInstanceOf(Answer::class, $answerReport->answer);
        $this->assertNull($answerReport->question_id);
        $this->assertTrue($answerReport->isAboutAnswer());
        $this->assertInstanceOf(User::class, $answerReport->reporter);
    }

    public function test_deleting_question_cascades_to_answers_ai_answer_and_reports(): void
    {
        $question = Question::factory()->withAi()->create();
        AiAnswer::factory()->for($question)->create();
        $answer = Answer::factory()->for($question)->create();
        Report::factory()->create(['question_id' => $question->id]);
        Report::factory()->forAnswer($answer)->create();

        $question->delete();

        $this->assertSame(0, Answer::count());
        $this->assertSame(0, AiAnswer::count());
        $this->assertSame(0, Report::count());
    }

    public function test_category_with_questions_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();
        Question::factory()->for($category)->create();

        $this->expectException(QueryException::class);
        $category->delete();
    }

    public function test_ai_answer_is_unique_per_question(): void
    {
        $question = Question::factory()->withAi()->create();
        AiAnswer::factory()->for($question)->create();

        $this->expectException(QueryException::class);
        AiAnswer::factory()->for($question)->create();
    }

    /**
     * 通報のユニーク制約は question_id / answer_id の片方が必ず NULL のため、
     * DB だけでは重複を防げない（設計書の注記どおり、アプリ側のチェックが必須）。
     * 重複防止そのものは Phase 7 の通報機能のテストで確認する。
     */
    public function test_report_unique_index_does_not_block_duplicates_because_of_null(): void
    {
        $report = Report::factory()->create();

        Report::factory()->create([
            'user_id' => $report->user_id,
            'question_id' => $report->question_id,
        ]);

        $this->assertSame(2, Report::count());
    }

    public function test_categories_are_ordered_by_sort_order(): void
    {
        Category::factory()->create(['name' => 'B', 'sort_order' => 2]);
        Category::factory()->create(['name' => 'A', 'sort_order' => 1]);

        $this->assertSame(['A', 'B'], Category::ordered()->pluck('name')->all());
    }
}
