<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use App\Services\Ranking\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * G15 ユーザー管理（一覧・検索・利用停止／再開）
 */
class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 9, 1));
        $this->admin = User::factory()->admin()->create(['name' => '管理者', 'email' => 'admin@school.ac.jp']);
        $this->travelBack();
    }

    public function test_list_shows_columns_in_id_order_and_admin_row_has_no_button(): void
    {
        $user = User::factory()->create(['name' => '山田 太郎', 'email' => 'yamada@school.ac.jp']);
        $suspended = User::factory()->suspended()->create(['name' => '匿名 太郎']);
        Answer::factory()->for($user)->count(3)->create();

        $html = $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeTextInOrder(['ユーザー管理', 'キーワード', '検索', 'ID', 'ユーザー名', 'メールアドレス', '権限', '回答数', '状態', '登録日', '操作',
                (string) $this->admin->id, '管理者', 'admin@school.ac.jp', '管理者', '0', '利用中', '2026/09/01',
                (string) $user->id, '山田 太郎', 'yamada@school.ac.jp', '一般', '3', '利用中', '利用停止',
                (string) $suspended->id, '匿名 太郎', '一般', '0', '利用停止', '利用再開'])
            ->getContent();

        $this->assertStringNotContainsString(route('admin.users.suspend', $this->admin), $html);
        $this->assertStringContainsString(route('admin.users.suspend', $user), $html);
        $this->assertStringContainsString(route('admin.users.resume', $suspended), $html);
    }

    public function test_answer_count_uses_the_same_definition_as_ranking(): void
    {
        $user = User::factory()->create();
        Answer::factory()->for($user)->count(2)->create();
        Answer::factory()->for($user)->hidden()->create();
        Answer::factory()->for($user)->for(Question::factory()->hidden())->create();

        $row = $this->actingAs($this->admin)->get(route('admin.users.index'))->viewData('users')->firstWhere('id', $user->id);

        $this->assertSame(2, (int) $row->answer_count);
        $this->assertSame(app(RankingService::class)->answerCount($user), (int) $row->answer_count);
    }

    public function test_keyword_searches_name_and_email_with_like_wildcards_as_text(): void
    {
        User::factory()->create(['name' => '情報 花子', 'email' => 'hanako@school.ac.jp']);
        User::factory()->create(['name' => '経済 一郎', 'email' => 'ichiro_k@school.ac.jp']);
        User::factory()->create(['name' => '理工 次郎', 'email' => 'ichiroXk@school.ac.jp']);

        $this->actingAs($this->admin)->get(route('admin.users.index', ['keyword' => '花子']))
            ->assertSee('情報 花子')->assertDontSee('経済 一郎');
        $this->actingAs($this->admin)->get(route('admin.users.index', ['keyword' => 'hanako@']))
            ->assertSee('情報 花子')->assertDontSee('理工 次郎');
        $this->actingAs($this->admin)->get(route('admin.users.index', ['keyword' => 'ichiro_k']))
            ->assertSee('経済 一郎')->assertDontSee('理工 次郎');
        $this->actingAs($this->admin)->get(route('admin.users.index', ['keyword' => '%']))
            ->assertSee('該当するユーザーはいません');
    }

    public function test_twenty_per_page_with_keyword_kept(): void
    {
        User::factory()->count(21)->sequence(fn ($s) => ['name' => sprintf('学生%02d', $s->index + 1)])->create();

        $this->actingAs($this->admin)->get(route('admin.users.index', ['keyword' => '学生']))
            ->assertSee('学生20')->assertDontSee('学生21')
            ->assertSee('keyword=%E5%AD%A6%E7%94%9F', false);
        $this->actingAs($this->admin)->get(route('admin.users.index', ['keyword' => '学生', 'page' => 2]))->assertSee('学生21');
    }

    public function test_suspend_logs_user_out_on_next_access_and_removes_from_ranking(): void
    {
        $user = User::factory()->create();
        Answer::factory()->for($user)->create();
        $this->assertNotNull(app(RankingService::class)->forUser($user));

        $this->actingAs($this->admin)->from(route('admin.users.index', ['keyword' => 'x']))
            ->post(route('admin.users.suspend', $user))
            ->assertRedirect(route('admin.users.index', ['keyword' => 'x']))
            ->assertSessionHas('success', '利用を停止しました');

        $this->assertSame(UserStatus::Suspended, $user->refresh()->status);
        $this->assertNull(app(RankingService::class)->forUser($user));
        $this->actingAs($user)->get(route('home'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'このアカウントは利用停止されています']);
    }

    public function test_resume_restores_access(): void
    {
        $user = User::factory()->suspended()->create();

        $this->actingAs($this->admin)->post(route('admin.users.resume', $user))
            ->assertSessionHas('success', '利用を再開しました');

        $this->assertSame(UserStatus::Active, $user->refresh()->status);
        $this->actingAs($user)->get(route('home'))->assertOk();
    }

    public function test_admins_including_self_cannot_be_suspended(): void
    {
        $otherAdmin = User::factory()->admin()->create();

        $this->actingAs($this->admin)->post(route('admin.users.suspend', $this->admin))->assertForbidden();
        $this->actingAs($this->admin)->post(route('admin.users.suspend', $otherAdmin))->assertForbidden();

        $this->assertTrue($this->admin->refresh()->isActive());
        $this->assertTrue($otherAdmin->refresh()->isActive());
    }

    public function test_texts_are_escaped(): void
    {
        User::factory()->create(['name' => '<script>alert(1)</script>']);

        $html = $this->actingAs($this->admin)->get(route('admin.users.index'))->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_general_user_gets_403(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.users.suspend', $target))->assertForbidden();
        $this->assertTrue($target->refresh()->isActive());
    }
}
