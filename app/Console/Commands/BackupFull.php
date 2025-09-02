<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupFull extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:full 
                            {--name= : Custom backup name}
                            {--encrypt : Encrypt the backup}
                            {--no-compress : Disable compression}
                            {--storage= : Storage driver (local, s3)}
                            {--retention= : Retention days (default: 30)}
                            {--include-tables=* : Include specific tables only}
                            {--exclude-tables=* : Exclude specific tables}
                            {--backup-directories=* : Directories to backup}
                            {--exclude-patterns=* : File patterns to exclude}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a full backup (database + files) with encryption and compression options';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Starting full backup (database + files)...');
        
        try {
            $config = [
                'name' => $this->option('name'),
                'encrypt' => $this->option('encrypt'),
                'compress' => !$this->option('no-compress'),
                'storage_driver' => $this->option('storage') ?? setting('storage.driver', 'private'),
                'retention_days' => (int) $this->option('retention') ?: 30,
                'include_tables' => $this->option('include-tables'),
                'exclude_tables' => $this->option('exclude-tables') ?: ['backup_logs', 'failed_jobs', 'sessions'],
                'backup_directories' => $this->option('backup-directories') ?: [
                    'storage/app/private',
                    'storage/app/public',
                    '.env',
                ],
                'exclude_patterns' => $this->option('exclude-patterns') ?: [
                    '*.log',
                    'storage/app/backups/*',
                    'storage/framework/cache/*',
                    'storage/framework/sessions/*',
                    'storage/framework/views/*',
                ],
            ];
            
            $backupLog = $backupService->createFullBackup($config);
            
            $this->info("Full backup completed successfully!");
            $this->table(['Field', 'Value'], [
                ['Backup ID', $backupLog->id],
                ['Backup Name', $backupLog->backup_name],
                ['File Size', $backupLog->formatted_size],
                ['Storage', $backupLog->storage_driver],
                ['Encrypted', $backupLog->is_encrypted ? 'Yes' : 'No'],
                ['Compressed', $backupLog->is_compressed ? 'Yes' : 'No'],
                ['Duration', $backupLog->duration ? $backupLog->duration . 's' : 'N/A'],
                ['Expires At', $backupLog->expires_at ? $backupLog->expires_at->format('Y-m-d H:i:s') : 'Never'],
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Full backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
