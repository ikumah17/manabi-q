<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * G17 通報（エラーはシート内の ④ に表示するため、エラーバッグ report を使う）
 */
class StoreReportRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'report';

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
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'detail' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => '通報理由を選択してください',
            'reason.enum' => '通報理由を選択してください',
        ];
    }

    public function reason(): ReportReason
    {
        return ReportReason::from((int) $this->validated('reason'));
    }
}
