<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $foodNames = [
            '招牌滷肉飯',
            '雞腿便當',
            '排骨飯',
            '炒飯',
            '炒麵',
            '牛肉麵',
            '陽春麵',
            '餛飩湯',
            '貢丸湯',
            '紫菜蛋花湯',
            '炸雞排',
            '鹹酥雞',
            '炸豆腐',
            '燙青菜',
            '滷蛋',
            '滷豆干',
            '珍珠奶茶',
            '紅茶',
            '綠茶',
            '檸檬汁',
        ];

        $descriptions = [
            '經典美味,不容錯過',
            '新鮮現做,份量十足',
            '招牌必點,人氣第一',
            '香氣四溢,口感絕佳',
            '精選食材,用心烹調',
            '傳統風味,回味無窮',
        ];

        return [
            'store_id' => \App\Models\Store::factory(),
            'category_id' => \App\Models\MenuCategory::factory(),
            'name' => fake()->randomElement($foodNames),
            'description' => fake()->randomElement($descriptions),
            'price' => fake()->randomFloat(0, 30, 200),
            'is_active' => true,
            'is_featured' => fake()->boolean(20), // 20% chance of being featured
            'is_sold_out' => false,
            'display_order' => fake()->numberBetween(0, 20),
        ];
    }

    /**
     * Indicate that the menu item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the menu item is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the menu item is sold out.
     */
    public function soldOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_sold_out' => true,
        ]);
    }

    /**
     * Set a specific price.
     */
    public function price(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
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
