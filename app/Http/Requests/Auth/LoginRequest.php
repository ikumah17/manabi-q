<?php

namespace App\Http\Requests\Auth;

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * G1 ログイン
 */
class LoginRequest extends FormRequest
{
    /**
     * 同じメールアドレスで 1 分間に失敗できる回数
     */
    public const MAX_ATTEMPTS = 5;

    public const DECAY_SECONDS = 60;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:64'],
        ];
    }

    /**
     * 認証に成功したユーザーを返す（ログイン処理はコントローラで行う）
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $credentials = $this->only('email', 'password');

        if (! Auth::validate($credentials)) {
            RateLimiter::hit($this->throttleKey(), self::DECAY_SECONDS);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /** @var User $user */
        $user = Auth::getLastAttempted();

        if (! $user->isActive()) {
            throw ValidationException::withMessages(['email' => EnsureUserIsActive::MESSAGE]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($this->throttleKey())]),
        ]);
    }

    /**
     * 制限はメールアドレス単位（大文字・小文字は区別しない）
     */
    private function throttleKey(): string
    {
        return 'login:'.Str::transliterate(Str::lower($this->string('email')));
    }
}
