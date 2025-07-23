<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:cleanup 
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompts}
                            {--older-than= : Delete backups older than specified days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired backups and free storage space';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info('Scanning for expired backups...');
        
        // Get expired backups
        $query = BackupLog::query();
        
        if ($olderThan = $this->option('older-than')) {
            $cutoffDate = now()->subDays((int) $olderThan);
            $query->where('created_at', '<', $cutoffDate);
            $this->info("Looking for backups older than {$olderThan} days (before {$cutoffDate->format('Y-m-d H:i:s')})...");
        } else {
            $query->expired();
            $this->info('Looking for backups past their retention period...');
        }
        
        $expiredBackups = $query->get();
        
        if ($expiredBackups->isEmpty()) {
            $this->info('✅ No expired backups found. Nothing to clean up.');
            return Command::SUCCESS;
        }
        
        // Calculate total size
        $totalSize = $expiredBackups->sum('file_size');
        $formattedSize = $this->formatBytes($totalSize);
        
        $this->info("Found {$expiredBackups->count()} expired backup(s) using {$formattedSize} of storage.");
        
        // Display backups to be deleted
        $this->table(['ID', 'Name', 'Type', 'Created', 'Size', 'Storage'], 
            $expiredBackups->map(function ($backup) {
                return [
                    $backup->id,
                    $backup->backup_name,
                    ucfirst($backup->backup_type),
                    $backup->created_at->format('Y-m-d H:i'),
                    $backup->formatted_size,
                    $backup->storage_driver,
                ];
            })->toArray()
        );
        
        // Dry run mode
        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE: No files will be deleted.');
            $this->info("Would delete {$expiredBackups->count()} backup(s) and free {$formattedSize} of storage.");
            return Command::SUCCESS;
        }
        
        // Confirmation
        if (!$this->option('force')) {
            $this->warn("⚠️  This will permanently delete {$expiredBackups->count()} backup(s) and free {$formattedSize} of storage.");
            
            if (!$this->confirm('Are you sure you want to proceed?')) {
                $this->info('Cleanup cancelled.');
                return Command::SUCCESS;
            }
        }
        
        // Perform cleanup
        $this->info('Starting cleanup...');
        
        $results = [
            'deleted' => 0,
            'failed' => 0,
            'freed_space' => 0,
        ];
        
        $progressBar = $this->output->createProgressBar($expiredBackups->count());
        $progressBar->start();
        
        foreach ($expiredBackups as $backup) {
            try {
                // Delete the backup file
                $storageDriver = $backup->storage_driver;
                $disk = $storageDriver === 's3' ? \Storage::disk('s3') : \Storage::disk('private');
                
                if ($backup->file_path && $disk->exists($backup->file_path)) {
                    $disk->delete($backup->file_path);
                }
                
                // Track freed space
                $results['freed_space'] += $backup->file_size ?: 0;
                
                // Delete the backup log record
                $backup->delete();
                
                $results['deleted']++;
                
            } catch (\Exception $e) {
                $results['failed']++;
                $this->line("\n❌ Failed to delete backup {$backup->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Display results
        $freedSpace = $this->formatBytes($results['freed_space']);
        
        $this->info('Cleanup Results:');
        $this->table(['Status', 'Count'], [
            ['✅ Deleted', $results['deleted']],
            ['❌ Failed', $results['failed']],
            ['💾 Space Freed', $freedSpace],
        ]);
        
        if ($results['deleted'] > 0) {
            $this->info("✅ Successfully cleaned up {$results['deleted']} backup(s) and freed {$freedSpace} of storage.");
        }
        
        if ($results['failed'] > 0) {
            $this->warn("⚠️  {$results['failed']} backup(s) could not be deleted. Check the logs for details.");
        }
        
        return $results['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
    
    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
