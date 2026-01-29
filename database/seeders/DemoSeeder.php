<?php

namespace Database\Seeders;

use Database\Seeders\Demo\DemoApplicationsSeeder;
use Database\Seeders\Demo\DemoAssetsSeeder;
use Database\Seeders\Demo\DemoAuditsSeeder;
use Database\Seeders\Demo\DemoCertificationsSeeder;
use Database\Seeders\Demo\DemoContext;
use Database\Seeders\Demo\DemoImplementationsSeeder;
use Database\Seeders\Demo\DemoPoliciesSeeder;
use Database\Seeders\Demo\DemoProgramsSeeder;
use Database\Seeders\Demo\DemoRelationshipsSeeder;
use Database\Seeders\Demo\DemoRisksSeeder;
use Database\Seeders\Demo\DemoStandardsSeeder;
use Database\Seeders\Demo\DemoSurveysSeeder;
use Database\Seeders\Demo\DemoTrustCenterSeeder;
use Database\Seeders\Demo\DemoUsersSeeder;
use Database\Seeders\Demo\DemoVendorsSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    /**
     * Run the database demo seeds.
     */
    public function run(): void
    {
        // Create shared context for all demo seeders
        $context = new DemoContext;

        DB::transaction(function () use ($context) {
            // Core entities (must be seeded first)
            (new DemoUsersSeeder($context))->run();
            (new DemoProgramsSeeder($context))->run();
            (new DemoStandardsSeeder($context))->run();
            (new DemoImplementationsSeeder($context))->run();
            (new DemoPoliciesSeeder($context))->run();

            // Vendor management
            (new DemoVendorsSeeder($context))->run();
            (new DemoApplicationsSeeder($context))->run();
            (new DemoAssetsSeeder($context))->run();

            // Risk and compliance
            (new DemoRisksSeeder($context))->run();
            (new DemoAuditsSeeder($context))->run();
            (new DemoSurveysSeeder($context))->run();

            // Trust center
            (new DemoCertificationsSeeder($context))->run();
            (new DemoTrustCenterSeeder($context))->run();

            // Cross-entity relationships (must be seeded last)
            (new DemoRelationshipsSeeder($context))->run();
        });
    }
}
