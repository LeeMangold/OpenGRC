<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SampleSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SP800171r2Seeder::class);
        $this->call(SP800171r3Seeder::class);
        $this->call(CSCSeeder::class);
        $this->call(SP80053LowSeeder::class);
        $this->call(DemoSeeder::class);
        $this->call(HipaaSeeder::class);
    }
}
