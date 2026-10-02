<?php

namespace Database\Factories;

use App\Models\AiAnswer;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiAnswer>
 */
class AiAnswerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question_id' => Question::factory()->withAi(),
            'body' => fake()->realText(150),
            'model_name' => 'gemini-3.5-flash-lite',
        ];
    }
}
