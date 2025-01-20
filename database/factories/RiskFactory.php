<?php

namespace Database\Factories;

use App\Models\Risk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Risk>
 */
class RiskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => 'Open',
            'inherent_likelihood' => $this->faker->numberBetween(2, 5),
            'inherent_impact' => $this->faker->numberBetween(2, 5),
            'residual_likelihood' => $this->faker->numberBetween(1, 4),
            'residual_impact' => $this->faker->numberBetween(1, 4),
            'inherent_risk' => 0,
            'residual_risk' => 0,
        ];
    }
}
