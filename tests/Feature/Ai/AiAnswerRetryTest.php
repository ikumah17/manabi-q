<?php

namespace Tests\Feature\Ai;

use App\Actions\GenerateAiAnswer;
use App\Http\Controllers\AiAnswerController;
use App\Models\AiAnswer;
use App\Models\Question;
use App\Models\User;
use App\Services\AiAnswer\AiAnswerResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * AI回答の再試行（G6・G7 ③。質問者本人のみ、1つの質問につき1時間に3回まで）
 */
class AiAnswerRetryTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'gemini.test/v1beta/models/*';

    private User $author;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config([
            'services.gemini.key' => 'dummy-key-for-tests-0123456789',
            'services.gemini.model' => 'gemini-3.5-flash-lite',
            'services.gemini.base_url' => 'https://gemini.test/v1beta',
        ]);
        $this->author = User::factory()->create();
        $this->question = Question::factory()->for($this->author)->withAi()->create();
    }

    private static function success(string $text = '再試行で得た回答'): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]], 'finishReason' => 'STOP']]];
    }

    private function retry(?User $as = null, ?string $from = null)
    {
        return $this->actingAs($as ?? $this->author)
            ->from($from ?? route('questions.show', $this->question))
            ->post(route('questions.ai-answer.retry', $this->question));
    }

    public function test_retry_button_is_shown_only_to_author_when_answer_is_missing(): void
    {
        $this->actingAs($this->author)->get(route('questions.show', $this->question))
            ->assertSee('AI回答を再試行')
            ->assertSee(route('questions.ai-answer.retry', $this->question));

        $this->actingAs(User::factory()->create())->get(route('questions.show', $this->question))
            ->assertDontSee('AI回答を再試行');

        AiAnswer::factory()->for($this->question)->create();
        $this->actingAs($this->author)->get(route('questions.show', $this->question))
            ->assertDontSee('AI回答を再試行');
    }

    public function test_successful_retry_saves_answer_and_returns_to_detail(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::success())]);

        $this->retry()
            ->assertRedirect(route('questions.show', $this->question))
            ->assertSessionMissing('ai_error');

        $this->assertSame('再試行で得た回答', $this->question->aiAnswer()->value('body'));
        $this->actingAs($this->author)->get(route('questions.show', $this->question))
            ->assertSee('再試行で得た回答')
            ->assertDontSee('AI回答を再試行');
    }

    public function test_retry_from_complete_screen_returns_to_complete_screen(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::success())]);

        $this->retry(from: route('questions.complete', $this->question))
            ->assertRedirect(route('questions.complete', $this->question));
    }

    public function test_retry_never_returns_to_unrelated_url(): void
    {
        Http::fake([self::ENDPOINT => Http::response(self::success())]);

        $this->retry(from: 'https://evil.example.com/phish')
            ->assertRedirect(route('questions.show', $this->question));
    }

    public function test_failed_retry_shows_message_and_keeps_button(): void
    {
        Http::fake([self::ENDPOINT => Http::response([], 500)]);

        $this->retry()->assertSessionHas('ai_error', AiAnswerResult::FAILED_MESSAGE);

        $this->assertSame(0, AiAnswer::count());
    }

    public function test_rate_limited_retry_shows_limit_message(): void
    {
        Http::fake([self::ENDPOINT => Http::response([], 429)]);

        $this->retry()->assertSessionHas('ai_error', AiAnswerResult::LIMIT_MESSAGE);
    }

    public function test_retry_is_limited_to_three_times_per_question_per_hour(): void
    {
        // 3回分の失敗（5xx は1回再送するため各2回）→ 1時間後の成功
        $sequence = Http::sequence();
        foreach (range(1, AiAnswerController::RETRY_LIMIT_PER_HOUR * 2) as $i) {
            $sequence->push([], 500);
        }
        Http::fake([self::ENDPOINT => $sequence->push(self::success())]);

        foreach (range(1, AiAnswerController::RETRY_LIMIT_PER_HOUR) as $i) {
            $this->retry()->assertSessionHas('ai_error', AiAnswerResult::FAILED_MESSAGE);
        }

        // 4回目は Gemini を呼ばずに上限の文言
        $this->retry()->assertSessionHas('ai_error', AiAnswerResult::LIMIT_MESSAGE);
        Http::assertSentCount(AiAnswerController::RETRY_LIMIT_PER_HOUR * 2); // 5xx は1回再送するため

        // 1時間後は再試行できる
        $this->travel(61)->minutes();
        $this->retry()->assertSessionMissing('ai_error');
        $this->assertSame(1, AiAnswer::count());
    }

    public function test_user_hourly_limit_also_applies_to_retry(): void
    {
        Http::fake();
        foreach (range(1, GenerateAiAnswer::USER_LIMIT_PER_HOUR) as $i) {
            RateLimiter::hit('ai-answer:user:'.$this->author->id, 3600);
        }

        $this->retry()->assertSessionHas('ai_error', AiAnswerResult::LIMIT_MESSAGE);

        Http::assertNothingSent();
    }

    public function test_retry_while_generation_is_running_shows_busy_message(): void
    {
        Http::fake();
        $lock = Cache::lock('ai-answer:question:'.$this->question->id, 30);
        $lock->get();

        $this->retry()->assertSessionHas('ai_error', AiAnswerResult::BUSY_MESSAGE);

        Http::assertNothingSent();
        $lock->release();
    }

    public function test_only_author_can_retry(): void
    {
        Http::fake();

        $this->retry(User::factory()->create())->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_cannot_retry_when_ai_not_requested_or_already_answered(): void
    {
        Http::fake();
        $notRequested = Question::factory()->for($this->author)->create();
        AiAnswer::factory()->for($this->question)->create();

        $this->actingAs($this->author)->post(route('questions.ai-answer.retry', $notRequested))->assertForbidden();
        $this->retry()->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_hidden_question_is_not_found_and_guest_or_admin_cannot_retry(): void
    {
        Http::fake();
        $hidden = Question::factory()->for($this->author)->withAi()->hidden()->create();

        $this->actingAs($this->author)->post(route('questions.ai-answer.retry', $hidden))->assertNotFound();

        auth()->logout();
        $this->post(route('questions.ai-answer.retry', $this->question))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('questions.ai-answer.retry', $this->question))
            ->assertRedirect(route('admin.dashboard'));

        Http::assertNothingSent();
    }
}
