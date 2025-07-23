<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupRestore extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore 
                            {backup_id : The ID of the backup to restore}
                            {--database-only : Restore database only}
                            {--files-only : Restore files only}
                            {--overwrite-files : Overwrite existing files}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a backup with options for selective restoration';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $backupId = $this->argument('backup_id');
        $backupLog = BackupLog::find($backupId);
        
        if (!$backupLog) {
            $this->error("Backup with ID {$backupId} not found.");
            return Command::FAILURE;
        }
        
        if ($backupLog->status !== 'completed') {
            $this->error("Cannot restore incomplete backup (status: {$backupLog->status}).");
            return Command::FAILURE;
        }
        
        // Display backup information
        $this->info('Backup Information:');
        $this->table(['Field', 'Value'], [
            ['Backup ID', $backupLog->id],
            ['Backup Name', $backupLog->backup_name],
            ['Type', ucfirst($backupLog->backup_type)],
            ['Created At', $backupLog->created_at->format('Y-m-d H:i:s')],
            ['File Size', $backupLog->formatted_size],
            ['Storage', $backupLog->storage_driver],
            ['Encrypted', $backupLog->is_encrypted ? 'Yes' : 'No'],
            ['Verified', $backupLog->verified ? 'Yes' : 'No'],
        ]);
        
        // Safety confirmation
        if (!$this->option('force')) {
            $this->warn('⚠️  WARNING: This operation will overwrite existing data!');
            
            if (!$this->confirm('Are you sure you want to proceed with the restore?')) {
                $this->info('Restore operation cancelled.');
                return Command::SUCCESS;
            }
            
            if (!$this->confirm('This is your final confirmation. Proceed with restore?')) {
                $this->info('Restore operation cancelled.');
                return Command::SUCCESS;
            }
        }
        
        try {
            // Verify backup integrity first
            $this->info('Verifying backup integrity...');
            if (!$backupService->verifyBackup($backupLog)) {
                $this->error('Backup verification failed! Restore aborted for safety.');
                return Command::FAILURE;
            }
            $this->info('✅ Backup verification passed.');
            
            // Prepare restore options
            $options = [
                'restore_database' => !$this->option('files-only'),
                'restore_files' => !$this->option('database-only') && $backupLog->backup_type === 'full',
                'overwrite' => $this->option('overwrite-files'),
            ];
            
            $this->info('Starting restore operation...');
            
            if ($backupService->restoreBackup($backupLog, $options)) {
                $this->info('✅ Backup restored successfully!');
                
                if ($options['restore_database']) {
                    $this->warn('⚠️  Database has been restored. You may need to:');
                    $this->line('   - Clear application cache: php artisan cache:clear');
                    $this->line('   - Restart queue workers if using queues');
                    $this->line('   - Verify application functionality');
                }
                
                return Command::SUCCESS;
            } else {
                $this->error('Restore operation failed.');
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error('Restore failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
