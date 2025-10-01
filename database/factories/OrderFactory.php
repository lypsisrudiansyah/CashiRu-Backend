<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cashier_id' => User::factory(),
            'transaction_number' => 'TRX-' . strtoupper(Str::random(10)),
            'total' => $this->faker->randomFloat(2, 50, 2000),
            'total_quantity' => $this->faker->numberBetween(1, 10),
            'payment_method' => $this->faker->randomElement(['cash', 'credit_card']),
        ];
    }
}

