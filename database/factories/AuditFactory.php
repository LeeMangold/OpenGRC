<?php

namespace Database\Factories;

use App\Enums\WorkflowStatus;
use App\Models\Audit;
use App\Models\Standard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class AuditFactory extends Factory
{
    protected $model = Audit::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->text(100),
            'description' => $this->faker->text(512),
            'status' => $this->faker->randomElement(WorkflowStatus::class),
            'audit_type' => 'Controls',
            'sid' => Standard::inRandomOrder()->first()->id,
            'controls' => $this->faker->words(),
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(7),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'manager_id' => User::factory(),
        ];
    }
}
