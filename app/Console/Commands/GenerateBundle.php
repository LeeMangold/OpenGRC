<?php

namespace App\Console\Commands;

use App\Http\Controllers\BundleController;
use Illuminate\Console\Command;

class GenerateBundle extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opengrc:generate-bundle {code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Standards Bundle';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $code = $this->argument('code');
        $this->info("Generating Standards Bundle for $code...");
        $bundle = BundleController::generate($code);
        if (isset($bundle['error'])) {
            $this->error($bundle['error']);
        } else {
            $this->info($bundle['success']);
        }
    }
}
