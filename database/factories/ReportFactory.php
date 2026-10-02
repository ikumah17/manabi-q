<?php

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 既定は質問への通報。回答への通報は forAnswer() を使う
 *
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'question_id' => Question::factory(),
            'answer_id' => null,
            'reason' => fake()->randomElement(ReportReason::cases()),
            'detail' => null,
            'status' => ReportStatus::Pending,
        ];
    }

    /**
     * 回答への通報
     */
    public function forAnswer(?Answer $answer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'question_id' => null,
            'answer_id' => $answer?->id ?? Answer::factory(),
        ]);
    }

    /**
     * 対応済み
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReportStatus::Resolved]);
    }
}
