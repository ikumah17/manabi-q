<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * G12 プロフィール編集
 *
 * - パスワードの変更時のみ、現在のパスワードを照合する（設計書どおり、メールアドレスだけの変更では求めない）
 * - 現在のパスワード・新しいパスワードは、片方でも入力されたら両方必須
 */
class UpdateProfileRequest extends FormRequest
{
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
            'name' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'current_password' => ['nullable', 'required_with:new_password', 'string', 'max:64', 'current_password:web'],
            'new_password' => ['nullable', 'required_with:current_password', ...User::passwordRules()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required_with' => '現在のパスワードを入力してください',
            'current_password.current_password' => '現在のパスワードが正しくありません',
            'new_password.required_with' => '新しいパスワードを入力してください',
        ];
    }
}
