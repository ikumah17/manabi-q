<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * G1 ログイン／ログアウト
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_login_screen_is_displayed(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('ログイン')
            ->assertSee('メールアドレス')
            ->assertSee('パスワード')
            ->assertSee(route('register'));
    }

    public function test_general_user_logs_in_and_goes_to_home(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_logs_in_and_goes_to_dashboard(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'password123']);

        $this->post('/login', ['email' => $admin->email, 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_wrong_password_shows_design_error_message(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'wrongpass1'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません'])
            ->assertSessionHasInput('email', $user->email);

        $this->assertGuest();
    }

    public function test_unknown_email_shows_same_error_message(): void
    {
        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません']);

        $this->assertGuest();
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors([
                'email' => 'メールアドレスを入力してください',
                'password' => 'パスワードを入力してください',
            ]);
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $user = User::factory()->suspended()->create(['password' => 'password123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertSessionHasErrors(['email' => 'このアカウントは利用停止されています']);

        $this->assertGuest();
    }

    public function test_login_is_limited_to_five_failures_per_minute_per_email(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrongpass1'])
                ->assertSessionHasErrors(['email' => 'メールアドレスまたはパスワードが正しくありません']);
        }

        // 6回目は正しいパスワードでも拒否される
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password123']);
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('ログインの試行回数が多すぎます', session('errors')->first('email'));
        $this->assertGuest();

        // 別のメールアドレスは制限されない
        $other = User::factory()->create(['password' => 'password123']);
        $this->post('/login', ['email' => $other->email, 'password' => 'password123'])
            ->assertRedirect(route('home'));
    }

    public function test_limit_is_released_after_one_minute(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrongpass1']);
        }

        $this->travel(61)->seconds();

        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_resets_failure_count(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrongpass1']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);
        $this->post('/logout');

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrongpass1']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('home'));
    }

    public function test_login_returns_to_internal_page_opened_before_login(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        // 未ログインで保護されたページを開く → G1 へ（戻り先が記録される）
        $this->get('/home?page=2')->assertRedirect(route('login'));

        $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(url('/home?page=2'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function externalUrls(): array
    {
        return [
            'other domain' => ['https://evil.example.com/home'],
            'protocol relative' => ['//evil.example.com/home'],
            'same host as prefix of other domain' => ['http://localhost.evil.example.com/home'],
            'javascript scheme' => ['javascript:alert(1)'],
        ];
    }

    #[DataProvider('externalUrls')]
    public function test_login_never_redirects_to_external_url(string $url): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->withSession(['url.intended' => $url])
            ->post('/login', ['email' => $user->email, 'password' => 'password123'])
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_logged_in_user_opening_login_is_sent_to_own_top(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect(route('home'));
        $this->actingAs(User::factory()->admin()->create())->get('/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_returns_to_login_screen(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_requires_post(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/logout')->assertMethodNotAllowed();
        $this->assertAuthenticated();
    }
}
