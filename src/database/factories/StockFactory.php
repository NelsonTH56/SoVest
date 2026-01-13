<?php

namespace Database\Factories;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'symbol' => strtoupper($this->faker->lexify('????')),
            'company_name' => $this->faker->company(),
            'sector' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Energy', 'Consumer']),
            'active' => true,
            'created_at' => now(),
        ];
    }
}
