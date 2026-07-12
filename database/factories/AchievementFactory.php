<?php

namespace Database\Factories;

use App\Models\Achievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'name' => fake()->sentence(3),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(['🏆', '⭐', '🎖️', '💎', '🔥']),
            'reward_credits' => fake()->randomElement([0, 10, 25, 50, 100]),
            'is_hidden' => false,
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_hidden' => true,
        ]);
    }
}
