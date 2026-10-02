<?php

namespace Tests\Feature\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ロール（一般／管理者）と利用停止によるアクセス制御
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_top_page_redirects_by_login_state_and_role(): void
    {
        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('home'));

        $this->actingAs(User::factory()->admin()->create())->get('/')->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_is_sent_to_login_from_protected_pages(): void
    {
        $this->get('/home')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_general_user_can_open_home_but_gets_403_on_admin_pages(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/home')->assertOk();
        $this->get('/admin')->assertForbidden();
    }

    public function test_admin_can_open_admin_pages_but_is_sent_to_dashboard_from_general_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create(['name' => '管理者']));

        $this->get('/admin')->assertOk()->assertSee('管理者 さん');
        $this->get('/home')->assertRedirect(route('admin.dashboard'));
    }

    public function test_user_suspended_while_logged_in_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/home')->assertOk();

        // 管理者が利用停止にした
        $user->status = UserStatus::Suspended;
        $user->save();

        $this->get('/home')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'このアカウントは利用停止されています']);
        $this->assertGuest();
    }

    public function test_suspended_message_is_shown_on_login_screen(): void
    {
        $user = User::factory()->suspended()->create();

        $this->actingAs($user)
            ->followingRedirects()
            ->get('/home')
            ->assertOk()
            ->assertSee('このアカウントは利用停止されています');
    }

    /**
     * CSRF 検証そのものはテスト実行中はフレームワークが省略するため、
     * 各フォームにトークンが埋め込まれていることを確認する
     */
    public function test_auth_forms_include_csrf_token(): void
    {
        $this->get('/login')->assertSee('name="_token"', false);
        $this->get('/register')->assertSee('name="_token"', false);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertSee('name="_token"', false);
    }
}
