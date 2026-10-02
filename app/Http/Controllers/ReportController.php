<?php

namespace App\Http\Controllers;

use App\Actions\SubmitReport;
use App\Http\Requests\StoreReportRequest;
use App\Models\Answer;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;

/**
 * G17 通報（G7 の上のボトムシートから送信。権限はルートの can ミドルウェアで先に判定する）
 */
class ReportController extends Controller
{
    public const DUPLICATE_MESSAGE = 'この投稿は既に通報済みです';

    public function storeForQuestion(StoreReportRequest $request, Question $question, SubmitReport $submitReport): RedirectResponse
    {
        return $this->submit($request, $question, $question, $submitReport);
    }

    public function storeForAnswer(StoreReportRequest $request, Question $question, Answer $answer, SubmitReport $submitReport): RedirectResponse
    {
        return $this->submit($request, $question, $answer, $submitReport);
    }

    private function submit(StoreReportRequest $request, Question $question, Question|Answer $target, SubmitReport $submitReport): RedirectResponse
    {
        $created = $submitReport->handle($request->user(), $target, $request->reason(), $request->validated('detail'));

        if (! $created) {
            // ④ エラーメッセージ（重複通報）。シートを開き直すため入力を戻す
            return redirect()->route('questions.show', $question)
                ->withErrors(['reason' => self::DUPLICATE_MESSAGE], 'report')
                ->withInput();
        }

        return redirect()->route('questions.show', $question)->with('success', '通報を受け付けました');
    }
}
