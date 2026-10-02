<?php

namespace Tests\Feature\Ranking;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * G12 プロフィール編集
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->me = User::factory()->create(['name' => '山田 太郎', 'email' => 'yamada@example.com', 'password' => 'oldpass123']);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function update(array $overrides = [])
    {
        return $this->actingAs($this->me)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), array_merge([
                'name' => '山田 太郎',
                'email' => 'yamada@example.com',
                'current_password' => '',
                'new_password' => '',
            ], $overrides));
    }

    public function test_edit_screen_shows_current_values_and_back_to_mypage(): void
    {
        $this->actingAs($this->me)->get(route('profile.edit'))
            ->assertOk()
            ->assertSeeInOrder(['ユーザー名（表示名）', 'value="山田 太郎"', 'メールアドレス', 'value="yamada@example.com"',
                'パスワードを変更する場合のみ入力してください', '現在のパスワード', '新しいパスワード', '更新する'], false)
            ->assertSee('aria-label="戻る"', false)
            ->assertSee(route('mypage'));
    }

    public function test_mypage_has_profile_edit_button(): void
    {
        $this->actingAs($this->me)->get('/mypage')
            ->assertSeeText('プロフィール編集')
            ->assertSee(route('profile.edit'));
    }

    public function test_name_and_email_are_updated_and_returns_to_mypage_with_message(): void
    {
        $this->update(['name' => 'まなび花子', 'email' => 'hanako@example.com'])
            ->assertRedirect(route('mypage'))
            ->assertSessionHas('success', 'プロフィールを更新しました');

        $this->me->refresh();
        $this->assertSame('まなび花子', $this->me->name);
        $this->assertSame('hanako@example.com', $this->me->email);
        $this->assertTrue(Hash::check('oldpass123', $this->me->password), 'パスワードは変わらない');

        $this->actingAs($this->me)->get('/mypage')->assertSee('プロフィールを更新しました');
    }

    /**
     * 設計書どおり、メールアドレスだけの変更では現在のパスワードを求めない（安全面の論点は完了報告に記載）
     */
    public function test_email_can_be_changed_without_current_password(): void
    {
        $this->update(['email' => 'new-address@example.com'])->assertSessionHasNoErrors();

        $this->assertSame('new-address@example.com', $this->me->refresh()->email);
    }

    public function test_email_must_be_unique_except_own(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->update(['email' => 'taken@example.com'])
            ->assertSessionHasErrors(['email' => 'このメールアドレスは既に登録されています']);
        $this->assertSame('yamada@example.com', $this->me->refresh()->email);

        $this->update()->assertSessionHasNoErrors(); // 自分の今のアドレスのままは可
    }

    public function test_password_is_changed_with_correct_current_password_and_session_is_regenerated(): void
    {
        $this->actingAs($this->me)->get(route('profile.edit'));
        $before = session()->getId();

        $this->update(['current_password' => 'oldpass123', 'new_password' => 'newpass456'])
            ->assertRedirect(route('mypage'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('newpass456', $this->me->refresh()->password));
        $this->assertAuthenticatedAs($this->me);
        $this->assertNotSame($before, session()->getId());

        // 新しいパスワードでログインできる
        $this->post('/logout');
        $this->post('/login', ['email' => 'yamada@example.com', 'password' => 'newpass456'])->assertRedirect(route('home'));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $this->update(['current_password' => 'wrongpass1', 'new_password' => 'newpass456'])
            ->assertSessionHasErrors(['current_password' => '現在のパスワードが正しくありません']);

        $this->assertTrue(Hash::check('oldpass123', $this->me->refresh()->password));
    }

    public function test_both_password_fields_are_required_when_either_is_filled(): void
    {
        $this->update(['new_password' => 'newpass456'])
            ->assertSessionHasErrors(['current_password' => '現在のパスワードを入力してください']);

        $this->update(['current_password' => 'oldpass123'])
            ->assertSessionHasErrors(['new_password' => '新しいパスワードを入力してください']);

        $this->assertTrue(Hash::check('oldpass123', $this->me->refresh()->password));
    }

    public function test_new_password_must_be_8_to_64_half_width_alphanumerics(): void
    {
        $cases = [
            'short12' => '新しいパスワードは8文字以上で入力してください',
            str_repeat('a', 65) => '新しいパスワードは64文字以内で入力してください',
            'new-pass!' => '新しいパスワードは半角英数字のみで入力してください',
        ];

        foreach ($cases as $password => $message) {
            $this->update(['current_password' => 'oldpass123', 'new_password' => $password])
                ->assertSessionHasErrors(['new_password' => $message]);
        }

        $this->assertTrue(Hash::check('oldpass123', $this->me->refresh()->password));
    }

    public function test_name_and_email_validation(): void
    {
        $this->update(['name' => '', 'email' => ''])
            ->assertSessionHasErrors(['name' => 'ユーザー名を入力してください', 'email' => 'メールアドレスを入力してください']);

        $this->update(['name' => Str::repeat('あ', 21), 'email' => 'not-an-email'])
            ->assertSessionHasErrors(['name' => 'ユーザー名は20文字以内で入力してください', 'email' => 'メールアドレスを正しい形式で入力してください']);
    }

    public function test_role_and_status_cannot_be_changed_from_form(): void
    {
        $this->update(['role' => '1', 'status' => '0'])->assertSessionHasNoErrors();

        $this->me->refresh();
        $this->assertSame(UserRole::User, $this->me->role);
        $this->assertSame(UserStatus::Active, $this->me->status);
    }

    public function test_validation_errors_keep_input_and_are_shown(): void
    {
        $this->update(['name' => '', 'email' => 'changed@example.com']);

        $this->actingAs($this->me)->get(route('profile.edit'))
            ->assertSee('ユーザー名を入力してください')
            ->assertSee('value="changed@example.com"', false);
    }

    public function test_guest_and_admin_cannot_open_profile_edit(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get(route('profile.edit'))->assertRedirect(route('admin.dashboard'));
    }
}
