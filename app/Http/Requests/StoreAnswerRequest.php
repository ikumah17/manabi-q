<?php

namespace App\Http\Requests;

use App\Models\Stamp;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * G8 回答投稿
 */
class StoreAnswerRequest extends FormRequest
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
            'body' => ['required', 'string', 'max:2000'],
            'stamp_id' => [
                'nullable',
                'integer',
                'exists:stamps,id',
                // 獲得済み（投稿の時点の公開中の回答数が必要数以上）のスタンプのみ
                function (string $attribute, mixed $value, Closure $fail) {
                    $stamp = Stamp::find($value);

                    if ($stamp && ! $stamp->isEarnedWith($this->user()->publishedAnswerCount())) {
                        $fail('獲得していないスタンプは選択できません');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '回答本文',
        ];
    }
}
