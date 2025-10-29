<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuCategory>
 */
class MenuCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => \App\Models\Store::factory(),
            'name' => fake()->randomElement([
                '主食類',
                '湯品',
                '飲料',
                '小菜',
                '甜點',
                '特餐',
                '套餐',
                '早餐',
                '午餐',
                '晚餐',
                '宵夜',
                '素食',
            ]),
            'description' => fake()->sentence(),
            'icon' => fake()->randomElement(['🍚', '🍜', '🥤', '🥗', '🍰', '🍱', '☕', '🍕', '🍔', '🌮']),
            'display_order' => fake()->numberBetween(0, 10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific display order.
     */
    public function order(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'display_order' => $order,
        ]);
    }
}
