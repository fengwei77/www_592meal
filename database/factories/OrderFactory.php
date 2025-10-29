<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6)),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'total_amount' => $this->faker->randomFloat(50, 500, 0),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'preparing', 'ready', 'completed']),
            'notes' => $this->faker->sentence(),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'transfer']),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'refunded']),
            'pickup_time' => $this->faker->dateTimeBetween('+1 hour', '+3 days'),
        ];
    }
}