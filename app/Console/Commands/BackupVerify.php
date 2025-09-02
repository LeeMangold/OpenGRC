<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupVerify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:verify 
                            {backup_id? : The ID of the backup to verify (optional)}
                            {--all : Verify all unverified backups}
                            {--force : Re-verify already verified backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify backup integrity using checksums and file existence';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        if ($this->option('all')) {
            return $this->verifyAllBackups($backupService);
        }
        
        $backupId = $this->argument('backup_id');
        
        if (!$backupId) {
            $this->error('Please provide a backup ID or use --all to verify all backups.');
            return Command::FAILURE;
        }
        
        return $this->verifySingleBackup($backupService, $backupId);
    }
    
    /**
     * Verify a single backup
     */
    protected function verifySingleBackup(BackupService $backupService, int $backupId): int
    {
        $backupLog = BackupLog::find($backupId);
        
        if (!$backupLog) {
            $this->error("Backup with ID {$backupId} not found.");
            return Command::FAILURE;
        }
        
        if ($backupLog->status !== 'completed') {
            $this->error("Cannot verify incomplete backup (status: {$backupLog->status}).");
            return Command::FAILURE;
        }
        
        if ($backupLog->verified && !$this->option('force')) {
            $this->info("Backup {$backupId} is already verified (at {$backupLog->verified_at->format('Y-m-d H:i:s')}).");
            $this->line('Use --force to re-verify.');
            return Command::SUCCESS;
        }
        
        $this->info("Verifying backup {$backupId}...");
        
        try {
            $isValid = $backupService->verifyBackup($backupLog);
            
            if ($isValid) {
                $this->info("✅ Backup {$backupId} verification passed.");
                $this->table(['Field', 'Value'], [
                    ['Backup ID', $backupLog->id],
                    ['Backup Name', $backupLog->backup_name],
                    ['File Size', $backupLog->formatted_size],
                    ['Checksum', $backupLog->checksum ? substr($backupLog->checksum, 0, 16) . '...' : 'N/A'],
                    ['Verified At', $backupLog->fresh()->verified_at->format('Y-m-d H:i:s')],
                ]);
                return Command::SUCCESS;
            } else {
                $this->error("❌ Backup {$backupId} verification failed.");
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("Verification failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Verify all backups
     */
    protected function verifyAllBackups(BackupService $backupService): int
    {
        $query = BackupLog::completed();
        
        if (!$this->option('force')) {
            $query->where('verified', false);
        }
        
        $backups = $query->get();
        
        if ($backups->isEmpty()) {
            $this->info('No backups to verify.');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$backups->count()} backup(s) to verify.");
        
        $results = [
            'verified' => 0,
            'failed' => 0,
        ];
        
        $progressBar = $this->output->createProgressBar($backups->count());
        $progressBar->start();
        
        foreach ($backups as $backup) {
            try {
                if ($backupService->verifyBackup($backup)) {
                    $results['verified']++;
                } else {
                    $results['failed']++;
                    $this->line("\n❌ Failed: Backup {$backup->id} ({$backup->backup_name})");
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $this->line("\n❌ Error: Backup {$backup->id} - " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Display results
        $this->info('Verification Results:');
        $this->table(['Status', 'Count'], [
            ['✅ Verified', $results['verified']],
            ['❌ Failed', $results['failed']],
            ['Total', $backups->count()],
        ]);
        
        return $results['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
