<?php

namespace App\Services\Gemini;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use JsonException;

/**
 * Google Gemini API（generateContent）の呼び出し
 *
 * - 設定は config('services.gemini.*') から読む（env() は使わない）
 * - API キーは x-goog-api-key ヘッダで送り、URL・例外・ログには出さない
 * - 再送は接続エラーと 5xx のみ（429・その他の 4xx は再送しない）
 */
class GeminiClient
{
    private const CONNECT_TIMEOUT_SECONDS = 5;

    private const RETRY_SLEEP_MILLISECONDS = 500;

    /**
     * 本文を返さない終了理由（安全フィルタなど）
     */
    private const BLOCKED_FINISH_REASONS = ['SAFETY', 'BLOCKLIST', 'PROHIBITED_CONTENT', 'SPII', 'RECITATION', 'IMAGE_SAFETY'];

    /**
     * @throws GeminiException
     */
    public function generate(string $systemInstruction, string $userText): GeminiResult
    {
        $key = (string) config('services.gemini.key');
        $model = (string) config('services.gemini.model');

        if ($key === '' || $model === '') {
            throw new GeminiException(GeminiFailure::MissingKey);
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('services.gemini.base_url'), '/'))
                ->withHeaders(['x-goog-api-key' => $key])
                ->acceptJson()
                ->asJson()
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout((int) config('services.gemini.timeout', 10))
                ->retry(
                    max(0, (int) config('services.gemini.retries', 1)) + 1,
                    self::RETRY_SLEEP_MILLISECONDS,
                    fn ($exception) => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()),
                    throw: false,
                )
                ->post('models/'.rawurlencode($model).':generateContent', [
                    'systemInstruction' => ['parts' => [['text' => $systemInstruction]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $userText]]]],
                    'generationConfig' => ['temperature' => 0.3, 'maxOutputTokens' => 1024],
                ]);
        } catch (ConnectionException) {
            throw new GeminiException(GeminiFailure::Connection);
        }

        return $this->parse($response, $model);
    }

    /**
     * @throws GeminiException
     */
    private function parse(Response $response, string $model): GeminiResult
    {
        $status = $response->status();

        if ($status === 429) {
            throw new GeminiException(GeminiFailure::RateLimited, $status);
        }

        if ($response->serverError()) {
            throw new GeminiException(GeminiFailure::ServerError, $status);
        }

        if (! $response->successful()) {
            throw new GeminiException(GeminiFailure::ClientError, $status);
        }

        try {
            $data = json_decode($response->body(), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new GeminiException(GeminiFailure::InvalidResponse, $status);
        }

        if (! is_array($data)) {
            throw new GeminiException(GeminiFailure::InvalidResponse, $status);
        }

        // 入力そのものが安全フィルタで止められた
        if (! empty($data['promptFeedback']['blockReason'])) {
            throw new GeminiException(GeminiFailure::Blocked, $status);
        }

        $candidate = $data['candidates'][0] ?? null;

        if (! is_array($candidate)) {
            throw new GeminiException(GeminiFailure::EmptyResponse, $status);
        }

        if (in_array($candidate['finishReason'] ?? null, self::BLOCKED_FINISH_REASONS, true)) {
            throw new GeminiException(GeminiFailure::Blocked, $status);
        }

        // STOP・MAX_TOKENS（出力上限で打ち切り）などは、本文があれば成功として扱う
        $text = collect($candidate['content']['parts'] ?? [])
            ->map(fn ($part) => is_array($part) && is_string($part['text'] ?? null) ? $part['text'] : '')
            ->implode('');

        if (trim($text) === '') {
            throw new GeminiException(GeminiFailure::EmptyResponse, $status);
        }

        $modelName = is_string($data['modelVersion'] ?? null) && $data['modelVersion'] !== '' ? $data['modelVersion'] : $model;

        return new GeminiResult($text, Str::limit($modelName, 50, ''));
    }
}
