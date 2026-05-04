<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Master FCC sync — runs the full data pipeline end-to-end:
 *
 *   1. fcc:import-bulk  → every U.S. broadcast facility + license
 *      (CDBS facility.zip + am/fm/tv_eng_data.zip + party.zip)
 *
 *   2. fcc:import-asr   → every Antenna Structure Registration
 *      (data.fcc.gov ULS r_tower.zip + a_tower.zip)
 *
 *   3. db:seed FccOperationalSeeder → realistic operational data
 *      (EAS tests, Issues/Programs lists, public file documents,
 *       tower lighting inspections, political file, station log,
 *       regulatory fees, form filings) for the imported stations.
 *
 * This is the command an operator runs after deploying the FCC reskin
 * to populate every page in the app with real data.
 *
 * Usage:
 *   php artisan fcc:sync
 *   php artisan fcc:sync --quick      # smoke test (--limit=200, no licensees)
 *   php artisan fcc:sync --skip-asr   # skip ASR import (faster)
 *   php artisan fcc:sync --skip-bulk  # skip CDBS bulk (use existing data)
 */
class FccSyncCommand extends Command
{
    protected $signature = 'fcc:sync
                            {--quick : Smoke test (limit=200 per service, no licensee names)}
                            {--skip-bulk : Skip the CDBS bulk import}
                            {--skip-asr : Skip the ASR bulk import}
                            {--skip-lms : Skip the LMS FRN augmentation pass}
                            {--lms-batch=1000 : LMS augmentation batch size per run}
                            {--skip-operational : Skip generating EAS/IPL/etc demo data}';

    protected $description = 'Run the full FCC data pipeline (bulk + ASR + operational)';

    public function handle(): int
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('  OpenGRC FCC Compliance — Full Data Sync');
        $this->info('═══════════════════════════════════════════════════');
        $this->newLine();

        $quick = $this->option('quick');

        if (! $this->option('skip-bulk')) {
            $this->info('▶ Step 1/3: CDBS bulk import (every U.S. broadcast station)');
            $bulkOpts = ['--no-interaction' => true];
            if ($quick) {
                $bulkOpts['--limit'] = 200;
                $bulkOpts['--skip-licensees'] = true;
            }
            $exit = $this->call('fcc:import-bulk', $bulkOpts);
            if ($exit !== 0) {
                $this->error('  CDBS bulk import failed; aborting');
                return $exit;
            }
            $this->newLine();
        }

        if (! $this->option('skip-asr')) {
            $this->info('▶ Step 2/4: ASR bulk import (Antenna Structure Registrations)');
            $asrOpts = ['--no-interaction' => true];
            if ($quick) $asrOpts['--limit'] = 200;
            $exit = $this->call('fcc:import-asr', $asrOpts);
            if ($exit !== 0) {
                $this->warn('  ASR import returned non-zero; continuing');
            }
            $this->newLine();
        }

        if (! $this->option('skip-lms')) {
            $this->info('▶ Step 3/4: LMS augmentation (per-station FRN, polite + resumable)');
            $batch = $quick ? 50 : (int) $this->option('lms-batch');
            $exit = $this->call('fcc:import-lms', [
                '--limit' => $batch,
                '--no-interaction' => true,
            ]);
            if ($exit !== 0) {
                $this->warn('  LMS augmentation returned non-zero; continuing');
            }
            $this->line("  Re-run `php artisan fcc:import-lms` to continue augmenting more stations.");
            $this->newLine();
        }

        if (! $this->option('skip-operational')) {
            $this->info('▶ Step 4/4: Operational data (EAS tests / IPL / Public File / etc.)');
            $exit = $this->call('db:seed', [
                '--class' => 'Database\\Seeders\\FccOperationalSeeder',
                '--force' => true,
                '--no-interaction' => true,
            ]);
            if ($exit !== 0) {
                $this->warn('  Operational seeder returned non-zero; continuing');
            }
            $this->newLine();
        }

        $this->info('═══════════════════════════════════════════════════');
        $this->info('  Sync complete. Hard-refresh /app/dashboard');
        $this->info('═══════════════════════════════════════════════════');
        return self::SUCCESS;
    }
}
