<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

/**
 * 管理者アカウントを作成する。
 * パスワードは画面に表示されない入力で受け取り、出力・ログには一切出さない。
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
                            {--name= : 管理者のユーザー名（20文字以内）}
                            {--email= : ログインに使うメールアドレス}';

    protected $description = '管理者アカウントを作成する（パスワードは非表示で入力）';

    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('ユーザー名（20文字以内）', '管理者');
        $email = $this->option('email') ?? $this->ask('メールアドレス');
        $password = $this->secret('パスワード（半角英数字8〜64文字。入力内容は表示されません）');
        $confirmation = $this->secret('パスワード（確認）');

        $validator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'name' => ['required', 'string', 'max:20'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', ...User::passwordRules(), 'confirmed'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $user->role = UserRole::Admin;
        $user->status = UserStatus::Active;
        $user->save();

        $this->info("管理者アカウントを作成しました（ID: {$user->id} / メールアドレス: {$user->email}）");

        return self::SUCCESS;
    }
}
