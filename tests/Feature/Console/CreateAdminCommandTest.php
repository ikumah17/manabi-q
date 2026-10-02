<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD_PROMPT = 'パスワード（半角英数字8〜64文字。入力内容は表示されません）';

    private const CONFIRM_PROMPT = 'パスワード（確認）';

    public function test_creates_active_admin_with_hashed_password(): void
    {
        $this->artisan('admin:create', ['--name' => '管理者', '--email' => 'admin@example.com'])
            ->expectsQuestion(self::PASSWORD_PROMPT, 'Admin12345')
            ->expectsQuestion(self::CONFIRM_PROMPT, 'Admin12345')
            ->expectsOutputToContain('管理者アカウントを作成しました')
            ->doesntExpectOutputToContain('Admin12345')
            ->assertSuccessful();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame(UserStatus::Active, $admin->status);
        $this->assertTrue(Hash::check('Admin12345', $admin->password));
    }

    public function test_rejects_password_with_symbols_or_too_short(): void
    {
        $this->artisan('admin:create', ['--name' => '管理者', '--email' => 'admin@example.com'])
            ->expectsQuestion(self::PASSWORD_PROMPT, 'pass-123!')
            ->expectsQuestion(self::CONFIRM_PROMPT, 'pass-123!')
            ->expectsOutputToContain('パスワードは半角英数字のみで入力してください')
            ->assertFailed();

        $this->artisan('admin:create', ['--name' => '管理者', '--email' => 'admin@example.com'])
            ->expectsQuestion(self::PASSWORD_PROMPT, 'abc123')
            ->expectsQuestion(self::CONFIRM_PROMPT, 'abc123')
            ->expectsOutputToContain('パスワードは8文字以上で入力してください')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_rejects_mismatched_confirmation(): void
    {
        $this->artisan('admin:create', ['--name' => '管理者', '--email' => 'admin@example.com'])
            ->expectsQuestion(self::PASSWORD_PROMPT, 'Admin12345')
            ->expectsQuestion(self::CONFIRM_PROMPT, 'Admin99999')
            ->expectsOutputToContain('パスワードが確認用と一致しません')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->artisan('admin:create', ['--name' => '管理者', '--email' => 'admin@example.com'])
            ->expectsQuestion(self::PASSWORD_PROMPT, 'Admin12345')
            ->expectsQuestion(self::CONFIRM_PROMPT, 'Admin12345')
            ->expectsOutputToContain('このメールアドレスは既に登録されています')
            ->assertFailed();

        $this->assertSame(1, User::count());
    }
}
