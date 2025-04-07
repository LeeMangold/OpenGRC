<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company().' Program',
            'description' => $this->faker->paragraph(),
            'program_manager_id' => User::factory(),
            'last_audit_date' => $this->faker->date(),
            'scope_status' => $this->faker->randomElement(['In Scope', 'Out of Scope', 'Pending Review']),
        ];
    }
}
