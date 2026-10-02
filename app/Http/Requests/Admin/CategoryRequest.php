<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Support\CategoryName;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * G16 カテゴリの追加・編集
 * 編集のエラーは edit バッグに入れ、どの行を編集していたか（editing）を戻して行を開き直す
 */
class CategoryRequest extends FormRequest
{
    public const DUPLICATE_MESSAGE = '同じ名前のカテゴリが既に存在します';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('category')) {
            $this->errorBag = 'edit';
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignoreId = $this->route('category')?->id;

        return [
            'name' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, Closure $fail) use ($ignoreId) {
                    $key = CategoryName::key((string) $value);
                    $exists = Category::query()
                        ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                        ->pluck('name')
                        ->contains(fn (string $name) => CategoryName::key($name) === $key);

                    if ($exists) {
                        $fail(self::DUPLICATE_MESSAGE);
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer', 'between:0,999'],
        ];
    }

    /**
     * 項目名（lang の name は「ユーザー名」のため、ここで上書きする）
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'カテゴリ名',
            'sort_order' => '表示順',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sort_order.integer' => '表示順は0〜999の範囲で入力してください',
            'sort_order.between' => '表示順は0〜999の範囲で入力してください',
        ];
    }
}
