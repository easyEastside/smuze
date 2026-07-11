<?php

namespace Database\Factories;

use App\Models\ShopItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopItem>
 */
class ShopItemFactory extends Factory
{
    protected $model = ShopItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'price' => fake()->numberBetween(5, 500),
            'is_active' => true,
            'stock' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function limited(int $stock = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $stock,
        ]);
    }
}
