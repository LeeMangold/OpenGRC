<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bundle>
 */
class BundleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'Bundle-'.$this->faker->unique()->numberBetween(1, 100),
            'name' => $this->faker->sentence,
            'version' => $this->faker->numerify('#.#.#'),
            'description' => $this->faker->paragraph,
            'authority' => $this->faker->name,
            'source_url' => $this->faker->url,
            'filename' => $this->faker->slug.'.zip',
            'image' => $this->faker->slug.'.jpg',
            'type' => 'Standard',
        ];
    }
}
