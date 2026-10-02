<?php

namespace App\Actions;

use App\Models\Question;
use App\Services\AiAnswer\AiAnswerResult;
use App\Services\AiAnswer\PromptBuilder;
use App\Services\AiAnswer\ResponseFormatter;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\GeminiException;
use App\Services\Gemini\GeminiFailure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * AI即時回答の生成（G5 で use_ai=1 の質問のみ）。失敗しても例外は投げず結果を返す
 *
 * - DB トランザクションの外で呼ぶこと（質問の保存を確定させてから外部 API を呼ぶ）
 * - 質問ごとの排他ロックと生成済みの確認し直しで、二重呼び出しを防ぐ
 * - 無料枠を守るため、1ユーザーにつき1時間に USER_LIMIT_PER_HOUR 回まで（投稿時・再試行の合計）
 */
class GenerateAiAnswer
{
    public const USER_LIMIT_PER_HOUR = 10;

    private const LOCK_SECONDS = 30;

    public function __construct(
        private readonly GeminiClient $client,
        private readonly PromptBuilder $prompt,
        private readonly ResponseFormatter $formatter,
    ) {}

    public function handle(Question $question): AiAnswerResult
    {
        if (! $question->use_ai || ! $question->isPublished()) {
            return AiAnswerResult::Skipped;
        }

        if ($question->aiAnswer()->exists()) {
            return AiAnswerResult::AlreadyExists;
        }

        $lock = Cache::lock('ai-answer:question:'.$question->id, self::LOCK_SECONDS);

        if (! $lock->get()) {
            return AiAnswerResult::Busy;
        }

        try {
            // ロックを取ってから、別のリクエストで作られていないか確かめ直す
            if ($question->aiAnswer()->exists()) {
                return AiAnswerResult::AlreadyExists;
            }

            $userKey = 'ai-answer:user:'.$question->user_id;

            if (RateLimiter::tooManyAttempts($userKey, self::USER_LIMIT_PER_HOUR)) {
                return AiAnswerResult::LimitReached;
            }

            RateLimiter::hit($userKey, 3600);

            $question->loadMissing(['user', 'category']);

            try {
                $result = $this->client->generate($this->prompt->systemInstruction(), $this->prompt->userContent($question));
            } catch (GeminiException $e) {
                $this->logFailure($question, $e->failure->value, $e->status);

                return $e->failure === GeminiFailure::RateLimited
                    ? AiAnswerResult::LimitReached
                    : AiAnswerResult::Failed;
            }

            $body = $this->formatter->format($result->text);

            if ($body === '') {
                $this->logFailure($question, GeminiFailure::EmptyResponse->value, null);

                return AiAnswerResult::Failed;
            }

            try {
                $question->aiAnswer()->create(['body' => $body, 'model_name' => $result->modelName]);
            } catch (UniqueConstraintViolationException) {
                // ロックの期限切れなどで同時に作られた場合（質問1件につきAI回答は1件）
                return AiAnswerResult::AlreadyExists;
            }

            return AiAnswerResult::Created;
        } finally {
            $lock->release();
        }
    }

    /**
     * 失敗の記録。質問ID・失敗の種類・HTTP ステータスのみ（キー・プロンプト・応答本文は残さない）
     */
    private function logFailure(Question $question, string $reason, ?int $status): void
    {
        Log::warning('AI回答の生成に失敗しました', [
            'question_id' => $question->id,
            'reason' => $reason,
            'status' => $status,
        ]);
    }
}
