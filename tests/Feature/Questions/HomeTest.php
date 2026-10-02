<?php

namespace Tests\Feature\Questions;

use App\Models\AiAnswer;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G4 ホーム（質問一覧・検索・並び替え・ページネーション）
 */
class HomeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->user = User::factory()->create();
    }

    public function test_cards_show_category_state_ai_badge_title_excerpt_author_and_answer_count(): void
    {
        $author = User::factory()->create(['name' => '情報 花子']);
        $category = Category::factory()->create(['name' => '授業']);
        $question = Question::factory()->for($author)->for($category)->withAi()
            ->create(['title' => '固有値の求め方', 'body' => '行列の固有値を求める問題です']);
        AiAnswer::factory()->for($question)->create();
        Answer::factory()->for($question)->count(2)->create();
        Answer::factory()->for($question)->hidden()->create();

        $this->actingAs($this->user)->get('/home')
            ->assertOk()
            ->assertSeeInOrder(['授業', '未解決', 'AI回答あり', '固有値の求め方', '行列の固有値を求める問題です', '情報 花子'])
            ->assertSee('回答 2')
            ->assertSee(route('questions.show', $question))
            ->assertSee(route('questions.create'));
    }

    public function test_ai_badge_is_shown_only_when_ai_answer_exists(): void
    {
        Question::factory()->withAi()->create(['title' => 'AI希望だが未生成']);

        $this->actingAs($this->user)->get('/home')
            ->assertSee('AI希望だが未生成')
            ->assertDontSee('AI回答あり');
    }

    public function test_hidden_questions_are_not_listed(): void
    {
        Question::factory()->create(['title' => '公開中の質問']);
        Question::factory()->hidden()->create(['title' => '非表示の質問']);

        $this->actingAs($this->user)->get('/home')
            ->assertSee('公開中の質問')
            ->assertDontSee('非表示の質問');
    }

    public function test_newest_first_and_ten_per_page(): void
    {
        foreach (range(1, 12) as $i) {
            Question::factory()->create(['title' => "質問{$i}番", 'created_at' => now()->subMinutes(100 - $i)]);
        }

        $this->actingAs($this->user)->get('/home')
            ->assertSeeInOrder(['質問12番', '質問11番', '質問3番'])
            ->assertDontSee('質問2番')
            ->assertSee('rel="next"', false);

        $this->actingAs($this->user)->get('/home?page=2')
            ->assertSeeInOrder(['質問2番', '質問1番'])
            ->assertDontSee('質問3番');
    }

    public function test_keyword_matches_title_or_body_and_all_words(): void
    {
        Question::factory()->create(['title' => 'Laravelのマイグレーション', 'body' => 'エラーが出ます']);
        Question::factory()->create(['title' => '図書館の開館時間', 'body' => 'Laravelの本を借りたい']);
        Question::factory()->create(['title' => '線形代数のレポート', 'body' => '固有値']);

        $this->actingAs($this->user)->get('/home?keyword=Laravel')
            ->assertSee('Laravelのマイグレーション')
            ->assertSee('図書館の開館時間')
            ->assertDontSee('線形代数のレポート');

        // 全角スペース区切りは AND
        $this->actingAs($this->user)->get('/home?keyword='.urlencode('Laravel　エラー'))
            ->assertSee('Laravelのマイグレーション')
            ->assertDontSee('図書館の開館時間');
    }

    public function test_like_wildcards_in_keyword_are_treated_as_plain_characters(): void
    {
        // 本文は固定（ランダムな本文に記号が含まれると結果が変わるため）
        foreach (['達成率100%の方法', '達成率1000の方法', 'snake_caseの命名', 'snakeXcaseの命名', '感嘆符!の使い方'] as $title) {
            Question::factory()->create(['title' => $title, 'body' => '本文']);
        }

        $this->actingAs($this->user)->get('/home?keyword='.urlencode('100%'))
            ->assertSee('達成率100%の方法')
            ->assertDontSee('達成率1000の方法');

        $this->actingAs($this->user)->get('/home?keyword='.urlencode('snake_case'))
            ->assertSee('snake_caseの命名')
            ->assertDontSee('snakeXcaseの命名');

        $this->actingAs($this->user)->get('/home?keyword='.urlencode('%'))
            ->assertSee('達成率100%の方法')
            ->assertDontSee('snake_caseの命名');

        $this->actingAs($this->user)->get('/home?keyword='.urlencode('!'))
            ->assertSee('感嘆符!の使い方')
            ->assertDontSee('達成率100%の方法');
    }

    public function test_filter_by_category(): void
    {
        $lecture = Category::factory()->create(['name' => '授業']);
        $life = Category::factory()->create(['name' => '学校生活']);
        Question::factory()->for($lecture)->create(['title' => '授業の質問']);
        Question::factory()->for($life)->create(['title' => '生活の質問']);

        $this->actingAs($this->user)->get("/home?category={$lecture->id}")
            ->assertSee('授業の質問')
            ->assertDontSee('生活の質問');
    }

    public function test_sort_unresolved_only_and_most_answers(): void
    {
        $few = Question::factory()->create(['title' => '回答少ない', 'created_at' => now()->subHour()]);
        $many = Question::factory()->create(['title' => '回答多い', 'created_at' => now()->subDay()]);
        Question::factory()->resolved()->create(['title' => '解決済みの質問']);
        Answer::factory()->for($few)->create();
        Answer::factory()->for($many)->count(3)->create();
        Answer::factory()->for($few)->hidden()->count(5)->create(); // 非表示は数えない

        $this->actingAs($this->user)->get('/home?sort=unresolved')
            ->assertSee('回答少ない')
            ->assertDontSee('解決済みの質問');

        $this->actingAs($this->user)->get('/home?sort=answers')
            ->assertSeeInOrder(['回答多い', '回答少ない']);
    }

    public function test_invalid_search_values_are_ignored(): void
    {
        Question::factory()->create(['title' => '普通の質問']);

        $this->actingAs($this->user)->get('/home?category=9999&sort=hack&keyword[]=x')
            ->assertOk()
            ->assertSee('普通の質問');
    }

    public function test_search_conditions_are_kept_in_pagination_links(): void
    {
        foreach (range(1, 11) as $i) {
            Question::factory()->create(['title' => "検索対象{$i}"]);
        }

        $this->actingAs($this->user)->get('/home?keyword='.urlencode('検索対象').'&sort=new')
            ->assertSee('keyword=%E6%A4%9C%E7%B4%A2%E5%AF%BE%E8%B1%A1', false)
            ->assertSee('sort=new', false);
    }

    public function test_empty_result_message(): void
    {
        $this->actingAs($this->user)->get('/home?keyword=存在しない語')
            ->assertSee('該当する質問はありません');
    }
}
