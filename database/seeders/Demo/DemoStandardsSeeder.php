<?php

namespace Database\Seeders\Demo;

use App\Http\Controllers\BundleController;
use App\Models\Bundle;
use App\Models\Standard;
use Illuminate\Database\Seeder;

class DemoStandardsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        // Fetch bundle updates from the repository
        BundleController::retrieve();

        // Import OpenGRC-1.0 and TSC2017 bundles
        $bundleCodes = ['OpenGRC-1.0', 'TSC-2017'];

        foreach ($bundleCodes as $bundleCode) {
            $bundle = Bundle::where('code', $bundleCode)->first();
            if ($bundle) {
                BundleController::importBundle($bundle);
            }
        }

        // Retrieve the imported standards and their controls
        $openGrcStandard = Standard::where('code', 'OpenGRC-1.0')->first();
        $tscStandard = Standard::where('code', 'TSC2017')->first();

        if ($openGrcStandard) {
            $this->context->standards[] = $openGrcStandard;
            // Get controls and assign owners
            foreach ($openGrcStandard->controls as $control) {
                $control->update(['control_owner_id' => $this->context->users[array_rand($this->context->users)]->id]);
                $this->context->controls[] = $control;
            }
        }

        if ($tscStandard) {
            $this->context->standards[] = $tscStandard;
            // Get controls and assign owners - store TSC controls separately for implementation linking
            foreach ($tscStandard->controls as $control) {
                $control->update(['control_owner_id' => $this->context->users[array_rand($this->context->users)]->id]);
                $this->context->controls[] = $control;
                $this->context->tscControls[] = $control;
            }
        }
    }
}
