<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Enums\ApplicationType;
use App\Models\Application;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Application',
            'description' => fake()->sentence(10),
            'url' => fake()->url(),
            'owner_id' => User::factory(),
            'vendor_id' => Vendor::factory(),
            'type' => fake()->randomElement(ApplicationType::cases())->value,
            'status' => fake()->randomElement(ApplicationStatus::cases())->value,
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
