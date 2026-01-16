<?php

namespace Database\Factories;

use App\Models\Prediction;
use App\Models\User;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

class PredictionFactory extends Factory
{
    protected $model = Prediction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'stock_id' => Stock::factory(),
            'prediction_type' => $this->faker->randomElement(['Bullish', 'Bearish']),
            'target_price' => $this->faker->optional()->randomFloat(2, 50, 500),
            'prediction_date' => now(),
            'end_date' => now()->addDays($this->faker->numberBetween(7, 90))->format('Y-m-d'),
            'is_active' => 1,
            'accuracy' => null,
            'reasoning' => $this->faker->paragraph(),
        ];
    }

    /**
     * Indicate that the prediction is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => 0,
            'accuracy' => $this->faker->numberBetween(0, 100),
        ]);
    }

    /**
     * Indicate that the prediction is bullish
     */
    public function bullish(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'Bullish',
        ]);
    }

    /**
     * Indicate that the prediction is bearish
     */
    public function bearish(): static
    {
        return $this->state(fn (array $attributes) => [
            'prediction_type' => 'Bearish',
        ]);
    }
}
