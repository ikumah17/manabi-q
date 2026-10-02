<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G2 新規会員登録 → G3 会員登録完了
 */
class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * @return array<string, string>
     */
    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'name' => 'まなび太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_register_screen_is_displayed(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSeeInOrder(['ユーザー名（表示名）', 'メールアドレス', 'パスワード', 'パスワード（確認）', '登録する'])
            ->assertSee(route('login'));
    }

    public function test_user_is_registered_as_active_general_user_without_logging_in(): void
    {
        $this->post('/register', $this->validInput())
            ->assertRedirect(route('register.complete'));

        $user = User::where('email', 'taro@example.com')->firstOrFail();
        $this->assertSame('まなび太郎', $user->name);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(Hash::check('password123', $user->password));

        // 自動ログインしない
        $this->assertGuest();
    }

    public function test_register_cannot_set_role_or_status(): void
    {
        $this->post('/register', $this->validInput(['role' => 1, 'status' => 0]));

        $user = User::where('email', 'taro@example.com')->firstOrFail();
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
    }

    public function test_complete_screen_is_shown_after_registration_and_links_to_login(): void
    {
        $this->followingRedirects()
            ->post('/register', $this->validInput())
            ->assertOk()
            ->assertSee('会員登録が完了しました')
            ->assertSee(route('login'));

        $this->assertGuest();
    }

    public function test_complete_screen_opened_directly_goes_back_to_login(): void
    {
        $this->get('/register/complete')->assertRedirect(route('login'));
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $this->post('/register', $this->validInput())
            ->assertSessionHasErrors(['email' => 'このメールアドレスは既に登録されています']);

        $this->assertSame(1, User::count());
    }

    public function test_password_confirmation_must_match(): void
    {
        $this->post('/register', $this->validInput(['password_confirmation' => 'different1']))
            ->assertSessionHasErrors(['password' => 'パスワードが確認用と一致しません']);
    }

    public function test_password_must_be_8_to_64_half_width_alphanumerics(): void
    {
        $cases = [
            'short1' => 'パスワードは8文字以上で入力してください',
            str_repeat('a', 65) => 'パスワードは64文字以内で入力してください',
            'pass-word1' => 'パスワードは半角英数字のみで入力してください',
            'ｐａｓｓｗｏｒｄ１' => 'パスワードは半角英数字のみで入力してください',
        ];

        foreach ($cases as $password => $message) {
            $this->post('/register', $this->validInput(['password' => $password, 'password_confirmation' => $password]))
                ->assertSessionHasErrors(['password' => $message]);
        }

        $this->post('/register', $this->validInput(['password' => str_repeat('a', 64), 'password_confirmation' => str_repeat('a', 64)]))
            ->assertSessionHasNoErrors();
    }

    public function test_required_fields_and_lengths_are_validated(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors([
                'name' => 'ユーザー名を入力してください',
                'email' => 'メールアドレスを入力してください',
                'password' => 'パスワードを入力してください',
            ]);

        $this->post('/register', $this->validInput(['name' => Str::repeat('あ', 21), 'email' => 'not-an-email']))
            ->assertSessionHasErrors([
                'name' => 'ユーザー名は20文字以内で入力してください',
                'email' => 'メールアドレスを正しい形式で入力してください',
            ]);
    }

    public function test_registered_user_can_log_in_from_login_screen(): void
    {
        $this->post('/register', $this->validInput());

        $this->post('/login', ['email' => 'taro@example.com', 'password' => 'password123'])
            ->assertRedirect(route('home'));
    }
}
