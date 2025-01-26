<?php

namespace Database\Factories;

use App\Enums\RiskStatus;
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
        $inherent_likelihood = $this->faker->numberBetween(2, 5);
        $inherent_impact = $this->faker->numberBetween(2, 5);
        $residual_likelihood = $this->faker->numberBetween(1, 4);
        $residual_impact = $this->faker->numberBetween(1, 4);
        $risk_status = $this->faker->randomElement(RiskStatus::cases());

        return [
            'name' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => $risk_status,
            'inherent_likelihood' => $inherent_likelihood,
            'inherent_impact' => $inherent_impact,
            'residual_likelihood' => $residual_likelihood,
            'residual_impact' => $residual_impact,
            'inherent_risk' => $inherent_likelihood * $inherent_impact,
            'residual_risk' => $residual_likelihood * $residual_impact,
        ];
    }
}
