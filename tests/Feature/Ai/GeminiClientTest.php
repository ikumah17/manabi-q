<?php

namespace Tests\Feature\Ai;

use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\GeminiException;
use App\Services\Gemini\GeminiFailure;
use App\Services\Gemini\GeminiResult;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gemini API 呼び出し（実際の API は呼ばず Http::fake() で応答を作る）
 */
class GeminiClientTest extends TestCase
{
    /**
     * テスト用のダミーキー（本物のキーではない）
     */
    private const DUMMY_KEY = 'dummy-key-for-tests-0123456789';

    private const ENDPOINT = 'gemini.test/v1beta/models/*';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => self::DUMMY_KEY,
            'services.gemini.model' => 'gemini-3.5-flash-lite',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
            'services.gemini.timeout' => 10,
            'services.gemini.retries' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private static function successBody(string $text = '回答です', string $finishReason = 'STOP', array $overrides = []): array
    {
        return array_merge([
            'candidates' => [[
                'content' => ['role' => 'model', 'parts' => [['text' => $text]]],
                'finishReason' => $finishReason,
            ]],
            'modelVersion' => 'gemini-3.5-flash-lite-001',
        ], $overrides);
    }

    private function generate(): GeminiResult
    {
        return app(GeminiClient::class)->generate('システム指示', '質問の内容');
    }

    private function assertFailure(GeminiFailure $expected, ?int $status = null): void
    {
        try {
            $this->generate();
            $this->fail('GeminiException が投げられていない');
        } catch (GeminiException $e) {
            $this->assertSame($expected, $e->failure);
            $this->assertSame($status, $e->status);
            $this->assertStringNotContainsString(self::DUMMY_KEY, $e->getMessage());
        }
    }

    public function test_success_returns_text_and_model_version(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::successBody("1行目\n2行目"))]);

        $result = $this->generate();

        $this->assertSame("1行目\n2行目", $result->text);
        $this->assertSame('gemini-3.5-flash-lite-001', $result->modelName);
    }

    public function test_request_uses_header_key_configured_model_and_system_instruction(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::successBody())]);

        $this->generate();

        Http::assertSent(function (Request $request) {
            return $request->method() === 'POST'
                && $request->url() === 'https://gemini.test/v1beta/models/gemini-3.5-flash-lite:generateContent'
                && $request->hasHeader('x-goog-api-key', self::DUMMY_KEY)
                && ! str_contains($request->url(), self::DUMMY_KEY)
                && $request['systemInstruction']['parts'][0]['text'] === 'システム指示'
                && $request['contents'][0]['role'] === 'user'
                && $request['contents'][0]['parts'][0]['text'] === '質問の内容';
        });
    }

    public function test_model_name_can_be_replaced_by_config(): void
    {
        config(['services.gemini.model' => 'gemini-3.1-flash-lite']);
        Http::fake([self::ENDPOINT => Http::response(self::successBody(overrides: ['modelVersion' => null]))]);

        $result = $this->generate();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/models/gemini-3.1-flash-lite:generateContent'));
        $this->assertSame('gemini-3.1-flash-lite', $result->modelName);
    }

    public function test_missing_key_fails_without_sending_request(): void
    {
        config(['services.gemini.key' => '']);
        Http::fake();

        $this->assertFailure(GeminiFailure::MissingKey);
        Http::assertNothingSent();
    }

    public function test_rate_limit_429_is_not_retried(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => ['code' => 429]], 429)]);

        $this->assertFailure(GeminiFailure::RateLimited, 429);
        Http::assertSentCount(1);
    }

    public function test_other_client_errors_are_not_retried(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['error' => ['code' => 404, 'message' => 'model not found']], 404)]);

        $this->assertFailure(GeminiFailure::ClientError, 404);
        Http::assertSentCount(1);
    }

    public function test_server_error_is_retried_once_then_succeeds(): void
    {
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['error' => ['code' => 503]], 503)
            ->push(self::successBody('再送で成功'))]);

        $this->assertSame('再送で成功', $this->generate()->text);
        Http::assertSentCount(2);
    }

    public function test_server_error_twice_fails_after_one_retry(): void
    {
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['error' => ['code' => 500]], 500)
            ->push(['error' => ['code' => 503]], 503)]);

        $this->assertFailure(GeminiFailure::ServerError, 503);
        Http::assertSentCount(2);
    }

    public function test_connection_error_is_retried_then_fails(): void
    {
        Http::fake([self::ENDPOINT => Http::failedConnection()]);

        $this->assertFailure(GeminiFailure::Connection);
        Http::assertSentCount(2);
    }

    public function test_retries_can_be_disabled_by_config(): void
    {
        config(['services.gemini.retries' => 0]);
        Http::fake([self::ENDPOINT => Http::response([], 500)]);

        $this->assertFailure(GeminiFailure::ServerError, 500);
        Http::assertSentCount(1);
    }

    public function test_blocked_prompt_is_failure(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['promptFeedback' => ['blockReason' => 'SAFETY']])]);

        $this->assertFailure(GeminiFailure::Blocked, 200);
    }

    public function test_safety_finish_reason_is_failure_even_with_text(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::successBody('途中まで', 'SAFETY'))]);

        $this->assertFailure(GeminiFailure::Blocked, 200);
    }

    public function test_max_tokens_with_text_is_success(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::successBody('出力上限で途中まで', 'MAX_TOKENS'))]);

        $this->assertSame('出力上限で途中まで', $this->generate()->text);
    }

    public function test_max_tokens_without_text_is_failure(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::successBody('', 'MAX_TOKENS'))]);

        $this->assertFailure(GeminiFailure::EmptyResponse, 200);
    }

    public function test_empty_or_missing_candidates_are_failure(): void
    {
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(self::successBody("  \n "))
            ->push(['candidates' => []])]);

        $this->assertFailure(GeminiFailure::EmptyResponse, 200);
        $this->assertFailure(GeminiFailure::EmptyResponse, 200);
    }

    public function test_broken_json_is_invalid_response(): void
    {
        Http::fake([self::ENDPOINT => Http::response('<html>not json</html>', 200, ['Content-Type' => 'text/html'])]);

        $this->assertFailure(GeminiFailure::InvalidResponse, 200);
    }

    public function test_multiple_parts_are_joined(): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => '前半'], ['text' => '後半']]], 'finishReason' => 'STOP']],
        ])]);

        $this->assertSame('前半後半', $this->generate()->text);
    }

    public function test_long_model_version_is_truncated_to_50_chars(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::successBody(overrides: ['modelVersion' => str_repeat('m', 80)]))]);

        $this->assertSame(50, mb_strlen($this->generate()->modelName));
    }
}
