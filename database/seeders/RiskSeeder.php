<?php

namespace Database\Seeders;

use App\Models\Risk;
use Illuminate\Database\Seeder;

class RiskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Risk::factory()->count(50)->create();
    }
}
