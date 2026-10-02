<?php

namespace Database\Factories;

use App\Models\Stamp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stamp>
 */
class StampFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $count = fake()->randomElement([10, 50, 100]);

        return [
            'name' => "{$count}回答達成",
            'image_path' => "images/stamps/stamp-{$count}.svg",
            'required_count' => $count,
        ];
    }

    /**
     * 必要回答数を指定する
     */
    public function requiring(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => "{$count}回答達成",
            'image_path' => "images/stamps/stamp-{$count}.svg",
            'required_count' => $count,
        ]);
    }
}
