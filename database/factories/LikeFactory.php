<?php

namespace Database\Factories;

use App\Models\Answer;
use App\Models\Like;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * 既定は質問へのいいね。回答へのいいねは forAnswer() を使う
 *
 * @extends Factory<Like>
 */
class LikeFactory extends Factory
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
        ];
    }

    /**
     * 回答へのいいね
     */
    public function forAnswer(?Answer $answer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'question_id' => null,
            'answer_id' => $answer?->id ?? Answer::factory(),
        ]);
    }
}
