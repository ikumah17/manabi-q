<?php

namespace Tests\Feature\Admin;

use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G14 投稿管理の一覧（質問・回答の混在・検索・絞り込み・ページネーション）
 */
class PostListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    private function index(array $query = [])
    {
        return $this->actingAs($this->admin)->get(route('admin.posts.index', $query));
    }

    public function test_questions_and_answers_are_listed_together_newest_first(): void
    {
        $base = CarbonImmutable::create(2026, 9, 30, 13, 5, 0, 'Asia/Tokyo');
        $author = User::factory()->create(['name' => '山田 太郎']);
        $q = Question::factory()->for($author)->create(['title' => 'Laravelのマイグレーションでエラーが出ます', 'created_at' => $base]);
        Answer::factory()->for($q)->for(User::factory()->state(['name' => '文学 三子']))
            ->create(['body' => '教科書の第4章 例題3 がほぼ同じ問題なので参考になると思います', 'created_at' => $base->addDay()]);
        Question::factory()->hidden()->create(['title' => '不適切な質問', 'created_at' => $base->subDay()]);

        $this->index()->assertOk()->assertSeeTextInOrder([
            'キーワード', '種別', '状態', '検索', 'ID', '種別', '内容', '投稿者', '投稿日時', '状態', '操作',
            '回答', '教科書の第4章 例題3 がほぼ同じ問題なので参考になると思います', '文学 三子', '2026/10/01 13:05', '公開',
            (string) $q->id, '質問', 'Laravelのマイグレーションでエラーが出ます', '山田 太郎', '2026/09/30 13:05', '公開',
            '質問', '不適切な質問', '非表示',
        ]);
    }

    public function test_type_and_status_filters(): void
    {
        $q = Question::factory()->create(['title' => '公開の質問']);
        Question::factory()->hidden()->create(['title' => '非表示の質問']);
        Answer::factory()->for($q)->create(['body' => '公開の回答']);
        Answer::factory()->for($q)->hidden()->create(['body' => '非表示の回答']);

        $this->index(['type' => 'question'])->assertSee('公開の質問')->assertSee('非表示の質問')->assertDontSee('公開の回答');
        $this->index(['type' => 'answer'])->assertSee('公開の回答')->assertDontSee('公開の質問');
        $this->index(['status' => 'hidden'])->assertSee('非表示の質問')->assertSee('非表示の回答')->assertDontSee('公開の質問');
        $this->index(['type' => 'answer', 'status' => 'published'])->assertSee('公開の回答')->assertDontSee('非表示の回答')->assertDontSee('公開の質問');
        $this->index(['type' => 'hack', 'status' => 'hack'])->assertOk()->assertSee('公開の質問')->assertSee('非表示の回答');
    }

    public function test_keyword_matches_title_body_and_author_name_for_both_types(): void
    {
        $q = Question::factory()->for(User::factory()->state(['name' => '情報 花子']))->create(['title' => '図書館の開館時間', 'body' => '土日は？']);
        Answer::factory()->for($q)->for(User::factory()->state(['name' => '経済 一郎']))->create(['body' => '図書館は10時から']);
        Question::factory()->create(['title' => '別の質問', 'body' => '関係なし']);

        $this->index(['keyword' => '図書館'])->assertSee('図書館の開館時間')->assertSee('図書館は10時から')->assertDontSee('別の質問');
        $this->index(['keyword' => '土日'])->assertSee('図書館の開館時間')->assertDontSee('図書館は10時から');
        $this->index(['keyword' => '花子'])->assertSee('図書館の開館時間')->assertDontSee('図書館は10時から');
        $this->index(['keyword' => '一郎'])->assertSee('図書館は10時から')->assertDontSee('図書館の開館時間');
    }

    public function test_like_wildcards_are_plain_text(): void
    {
        Question::factory()->create(['title' => '達成率100%', 'body' => '本文']);
        Question::factory()->create(['title' => '達成率1000', 'body' => '本文']);

        $this->index(['keyword' => '100%'])->assertSee('達成率100%')->assertDontSee('達成率1000');
    }

    public function test_twenty_per_page_with_conditions_kept(): void
    {
        foreach (range(1, 21) as $i) {
            Question::factory()->create(['title' => sprintf('質問%02d', $i), 'created_at' => now()->subMinutes(100 - $i)]);
        }

        $this->index(['type' => 'question'])
            ->assertSee('質問21')->assertDontSee('質問01')
            ->assertSee('type=question', false)->assertSee('rel="next"', false);
        $this->index(['type' => 'question', 'page' => 2])->assertSee('質問01');
    }

    public function test_long_content_is_truncated_by_characters_with_full_text_in_title(): void
    {
        $body = str_repeat('あ', 39).'い'.str_repeat('う', 5);
        Answer::factory()->create(['body' => $body]);

        $this->index(['type' => 'answer'])
            ->assertSee(str_repeat('あ', 39).'い…')
            ->assertSee('title="'.$body.'"', false);
    }

    public function test_texts_are_escaped(): void
    {
        Question::factory()->for(User::factory()->state(['name' => '<b>name</b>']))->create(['title' => '<script>alert(1)</script>']);

        $html = $this->index()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<b>name</b>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_general_user_gets_403(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.posts.index'))->assertForbidden();
    }
}
