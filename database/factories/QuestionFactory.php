<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => Str::limit(fake()->realText(40), 100, ''),
            'body' => fake()->realText(200),
            'use_ai' => false,
            'is_resolved' => false,
            'status' => PostStatus::Published,
        ];
    }

    /**
     * AI回答を希望
     */
    public function withAi(): static
    {
        return $this->state(fn (array $attributes) => ['use_ai' => true]);
    }

    /**
     * 解決済み
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => ['is_resolved' => true]);
    }

    /**
     * 非表示
     */
    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PostStatus::Hidden]);
    }
}
