<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Answer;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Answer>
 */
class AnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory(),
            'user_id' => User::factory(),
            'stamp_id' => null,
            'body' => fake()->realText(150),
            'is_best' => false,
            'rating' => null,
            'status' => PostStatus::Published,
        ];
    }

    /**
     * ベストアンサー（★評価付き）
     */
    public function best(int $rating = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'is_best' => true,
            'rating' => $rating,
        ]);
    }

    /**
     * 非表示
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PostStatus::Hidden]);
    }
}
