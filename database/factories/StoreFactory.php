<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'subdomain' => fake()->unique()->slug(2, false),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'description' => fake()->sentence(10),
            'store_type' => fake()->randomElement(['restaurant', 'cafe', 'snack', 'bar', 'bakery', 'other']),
            'latitude' => fake()->latitude(22.0, 25.0), // 台灣地區緯度範圍
            'longitude' => fake()->longitude(120.0, 122.0), // 台灣地區經度範圍
            'business_hours' => [
                'monday' => [
                    'is_open' => true,
                    'opens_at' => '09:00',
                    'closes_at' => '18:00',
                ],
                'tuesday' => [
                    'is_open' => true,
                    'opens_at' => '09:00',
                    'closes_at' => '18:00',
                ],
                'wednesday' => [
                    'is_open' => true,
                    'opens_at' => '09:00',
                    'closes_at' => '18:00',
                ],
                'thursday' => [
                    'is_open' => true,
                    'opens_at' => '09:00',
                    'closes_at' => '18:00',
                ],
                'friday' => [
                    'is_open' => true,
                    'opens_at' => '09:00',
                    'closes_at' => '21:00',
                ],
                'saturday' => [
                    'is_open' => true,
                    'opens_at' => '10:00',
                    'closes_at' => '22:00',
                ],
                'sunday' => [
                    'is_open' => false,
                    'opens_at' => null,
                    'closes_at' => null,
                ],
            ],
            'logo_url' => fake()->imageUrl(200, 200, 'business'),
            'cover_image_url' => fake()->imageUrl(800, 400, 'business'),
            'social_links' => [
                'facebook' => fake()->userName(),
                'instagram' => fake()->userName(),
                'line' => fake()->regexify('[a-z0-9]{10}'),
                'website' => fake()->url(),
            ],
            'settings' => [
                'currency' => 'TWD',
                'timezone' => 'Asia/Taipei',
                'language' => 'zh-TW',
            ],
            'line_pay_settings' => [
                'enabled' => false,
                'approval_status' => 'pending',
            ],
            'is_active' => true,
        ];
    }

    /**
     * 創建屬於特定用戶的店家
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * 創建已停用的店家
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * 啟用 LINE Pay 設定
     */
    public function withLinePay(): static
    {
        return $this->state(fn (array $attributes) => [
            'line_pay_settings' => [
                'channel_id' => fake()->numerify('##########'),
                'channel_secret' => fake()->sha256(),
                'approval_status' => 'approved',
                'enabled' => true,
            ],
        ]);
    }

    /**
     * 創建餐廳類型的店家
     */
    public function restaurant(): static
    {
        return $this->state(fn (array $attributes) => [
            'store_type' => 'restaurant',
        ]);
    }

    /**
     * 創建咖啡廳類型的店家
     */
    public function cafe(): static
    {
        return $this->state(fn (array $attributes) => [
            'store_type' => 'cafe',
        ]);
    }
}
