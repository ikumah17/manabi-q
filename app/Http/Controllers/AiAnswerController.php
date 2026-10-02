<?php

namespace App\Http\Controllers;

use App\Actions\GenerateAiAnswer;
use App\Models\Question;
use App\Services\AiAnswer\AiAnswerResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

/**
 * AI回答の再試行（G6・G7 ③ で取得できなかった場合。質問者本人のみ）
 */
class AiAnswerController extends Controller
{
    /**
     * 1つの質問につき1時間に再試行できる回数
     */
    public const RETRY_LIMIT_PER_HOUR = 3;

    public function store(Request $request, Question $question, GenerateAiAnswer $generateAiAnswer): RedirectResponse
    {
        Gate::authorize('view', $question);
        Gate::authorize('retryAiAnswer', $question);

        $backUrl = $this->backUrl($question);
        $key = 'ai-answer:retry:'.$question->id;

        if (RateLimiter::tooManyAttempts($key, self::RETRY_LIMIT_PER_HOUR)) {
            return redirect()->to($backUrl)->with('ai_error', AiAnswerResult::LIMIT_MESSAGE);
        }

        RateLimiter::hit($key, 3600);

        $result = $generateAiAnswer->handle($question);

        return redirect()->to($backUrl)->with('ai_error', $result->message());
    }

    /**
     * 戻り先は、操作した画面（この質問の G6 または G7）に限る
     */
    private function backUrl(Question $question): string
    {
        $previous = url()->previous();
        $complete = route('questions.complete', $question);

        return $previous === $complete ? $complete : route('questions.show', $question);
    }
}
