<?php

namespace App\Services;

use App\Models\BackupLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use ZipArchive;

class BackupService
{
    protected string $backupPath;
    protected string $tempPath;

    public function __construct()
    {
        $this->backupPath = storage_path('app/backups');
        $this->tempPath = sys_get_temp_dir() . '/opengrc_backup_' . uniqid();
        
        // Ensure backup directory exists
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }
    }

    /**
     * Create a full backup (database + files)
     */
    public function createFullBackup(array $config = []): BackupLog
    {
        $backupLog = $this->createBackupLog('full', $config);
        
        try {
            $backupLog->markAsStarted();
            
            // Create temporary directory
            if (!is_dir($this->tempPath)) {
                mkdir($this->tempPath, 0755, true);
            }
            
            // Backup database
            $dbBackupPath = $this->backupDatabase($config);
            
            // Backup files
            $filesBackupPath = $this->backupFiles($config);
            
            // Create combined archive
            $finalBackupPath = $this->createCombinedArchive($dbBackupPath, $filesBackupPath, $backupLog->backup_name);
            
            // Calculate checksum
            $checksum = hash_file('sha256', $finalBackupPath);
            
            // Store backup
            $storedPath = $this->storeBackup($finalBackupPath, $backupLog->backup_name, $config);
            
            // Update backup log
            $backupLog->markAsCompleted([
                'file_path' => $storedPath,
                'file_name' => basename($storedPath),
                'file_size' => filesize($finalBackupPath),
                'checksum' => $checksum,
                'is_compressed' => true,
                'is_encrypted' => $config['encrypt'] ?? false,
            ]);
            
            // Cleanup temp files
            $this->cleanup();
            
            return $backupLog;
            
        } catch (\Exception $e) {
            $backupLog->markAsFailed($e->getMessage());
            $this->cleanup();
            throw $e;
        }
    }

    /**
     * Create a database-only backup
     */
    public function createDatabaseBackup(array $config = []): BackupLog
    {
        $backupLog = $this->createBackupLog('database', $config);
        
        try {
            $backupLog->markAsStarted();
            
            // Create temporary directory
            if (!is_dir($this->tempPath)) {
                mkdir($this->tempPath, 0755, true);
            }
            
            // Backup database
            $dbBackupPath = $this->backupDatabase($config);
            
            // Calculate checksum
            $checksum = hash_file('sha256', $dbBackupPath);
            
            // Store backup
            $storedPath = $this->storeBackup($dbBackupPath, $backupLog->backup_name, $config);
            
            // Update backup log
            $backupLog->markAsCompleted([
                'file_path' => $storedPath,
                'file_name' => basename($storedPath),
                'file_size' => filesize($dbBackupPath),
                'checksum' => $checksum,
                'is_compressed' => str_ends_with($dbBackupPath, '.gz'),
                'is_encrypted' => $config['encrypt'] ?? false,
            ]);
            
            // Cleanup temp files
            $this->cleanup();
            
            return $backupLog;
            
        } catch (\Exception $e) {
            $backupLog->markAsFailed($e->getMessage());
            $this->cleanup();
            throw $e;
        }
    }

    /**
     * Backup database
     */
    protected function backupDatabase(array $config = []): string
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");
        
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "database_backup_{$timestamp}.sql";
        $filepath = $this->tempPath . '/' . $filename;
        
        // Get tables to include/exclude
        $includeTables = $config['include_tables'] ?? [];
        $excludeTables = $config['exclude_tables'] ?? ['backup_logs', 'failed_jobs', 'sessions'];
        
        switch ($dbConfig['driver']) {
            case 'mysql':
                $this->backupMysql($dbConfig, $filepath, $includeTables, $excludeTables);
                break;
            case 'pgsql':
                $this->backupPostgres($dbConfig, $filepath, $includeTables, $excludeTables);
                break;
            case 'sqlite':
                $this->backupSqlite($dbConfig, $filepath);
                break;
            default:
                throw new \Exception("Unsupported database driver: {$dbConfig['driver']}");
        }
        
        // Compress if requested
        if ($config['compress'] ?? true) {
            $compressedPath = $filepath . '.gz';
            $this->compressFile($filepath, $compressedPath);
            unlink($filepath);
            return $compressedPath;
        }
        
        return $filepath;
    }

    /**
     * Backup MySQL database
     */
    protected function backupMysql(array $config, string $filepath, array $includeTables, array $excludeTables): void
    {
        $command = sprintf(
            'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database'])
        );
        
        // Add table filters
        if (!empty($excludeTables)) {
            foreach ($excludeTables as $table) {
                $command .= " --ignore-table={$config['database']}.{$table}";
            }
        }
        
        $command .= " > " . escapeshellarg($filepath);
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("MySQL backup failed with return code: {$returnCode}");
        }
    }

    /**
     * Backup PostgreSQL database
     */
    protected function backupPostgres(array $config, string $filepath, array $includeTables, array $excludeTables): void
    {
        $env = [
            'PGPASSWORD' => $config['password']
        ];
        
        $command = sprintf(
            'pg_dump --host=%s --port=%s --username=%s --format=custom --no-password %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database'])
        );
        
        // Add table filters
        foreach ($excludeTables as $table) {
            $command .= " --exclude-table={$table}";
        }
        
        $command .= " > " . escapeshellarg($filepath);
        
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes, null, $env);
        
        if (!is_resource($process)) {
            throw new \Exception("Failed to start PostgreSQL backup process");
        }
        
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            throw new \Exception("PostgreSQL backup failed: {$error}");
        }
    }

    /**
     * Backup SQLite database
     */
    protected function backupSqlite(array $config, string $filepath): void
    {
        $dbPath = $config['database'];
        
        if (!file_exists($dbPath)) {
            throw new \Exception("SQLite database file not found: {$dbPath}");
        }
        
        if (!copy($dbPath, $filepath)) {
            throw new \Exception("Failed to copy SQLite database file");
        }
    }

    /**
     * Backup files
     */
    protected function backupFiles(array $config = []): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "files_backup_{$timestamp}.zip";
        $filepath = $this->tempPath . '/' . $filename;
        
        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot create backup archive: {$filepath}");
        }
        
        // Default directories to backup
        $directories = $config['backup_directories'] ?? [
            'storage/app/private',
            'storage/app/public',
            '.env',
        ];
        
        $excludePatterns = $config['exclude_patterns'] ?? [
            '*.log',
            'storage/app/backups/*',
            'storage/framework/cache/*',
            'storage/framework/sessions/*',
            'storage/framework/views/*',
        ];
        
        foreach ($directories as $dir) {
            $fullPath = base_path($dir);
            
            if (is_file($fullPath)) {
                $zip->addFile($fullPath, $dir);
            } elseif (is_dir($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $dir, $excludePatterns);
            }
        }
        
        $zip->close();
        
        return $filepath;
    }

    /**
     * Add directory to ZIP archive recursively
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $realPath, string $archivePath, array $excludePatterns): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $archivePath . '/' . substr($filePath, strlen($realPath) + 1);
            
            // Check exclude patterns
            $excluded = false;
            foreach ($excludePatterns as $pattern) {
                if (fnmatch($pattern, $relativePath)) {
                    $excluded = true;
                    break;
                }
            }
            
            if (!$excluded && $file->isFile()) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    /**
     * Create combined archive
     */
    protected function createCombinedArchive(string $dbBackupPath, string $filesBackupPath, string $backupName): string
    {
        $filename = "{$backupName}.zip";
        $filepath = $this->tempPath . '/' . $filename;
        
        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception("Cannot create combined backup archive: {$filepath}");
        }
        
        $zip->addFile($dbBackupPath, 'database/' . basename($dbBackupPath));
        $zip->addFile($filesBackupPath, 'files/' . basename($filesBackupPath));
        
        $zip->close();
        
        return $filepath;
    }

    /**
     * Store backup to configured storage
     */
    protected function storeBackup(string $localPath, string $backupName, array $config = []): string
    {
        $storageDriver = $config['storage_driver'] ?? setting('storage.driver', 'private');
        $encrypt = $config['encrypt'] ?? false;
        
        $filename = basename($localPath);
        $storagePath = "backups/{$backupName}/{$filename}";
        
        // Encrypt if requested
        if ($encrypt) {
            $encryptedPath = $localPath . '.enc';
            $this->encryptFile($localPath, $encryptedPath);
            $localPath = $encryptedPath;
            $storagePath .= '.enc';
        }
        
        // Store based on driver
        if ($storageDriver === 's3') {
            Storage::disk('s3')->put($storagePath, file_get_contents($localPath));
        } else {
            Storage::disk('private')->put($storagePath, file_get_contents($localPath));
        }
        
        return $storagePath;
    }

    /**
     * Compress file using gzip
     */
    protected function compressFile(string $source, string $destination): void
    {
        $sourceHandle = fopen($source, 'rb');
        $destHandle = gzopen($destination, 'wb9');
        
        if (!$sourceHandle || !$destHandle) {
            throw new \Exception("Failed to open files for compression");
        }
        
        while (!feof($sourceHandle)) {
            gzwrite($destHandle, fread($sourceHandle, 8192));
        }
        
        fclose($sourceHandle);
        gzclose($destHandle);
    }

    /**
     * Encrypt file
     */
    protected function encryptFile(string $source, string $destination): void
    {
        $data = file_get_contents($source);
        $encrypted = Crypt::encrypt($data);
        file_put_contents($destination, $encrypted);
    }

    /**
     * Verify backup integrity
     */
    public function verifyBackup(BackupLog $backupLog): bool
    {
        try {
            $storageDriver = $backupLog->storage_driver;
            $disk = $storageDriver === 's3' ? Storage::disk('s3') : Storage::disk('private');
            
            if (!$disk->exists($backupLog->file_path)) {
                return false;
            }
            
            // Verify checksum if available
            if ($backupLog->checksum) {
                $tempFile = tempnam(sys_get_temp_dir(), 'backup_verify_');
                file_put_contents($tempFile, $disk->get($backupLog->file_path));
                
                $currentChecksum = hash_file('sha256', $tempFile);
                unlink($tempFile);
                
                if ($currentChecksum !== $backupLog->checksum) {
                    return false;
                }
            }
            
            // Update verification status
            $backupLog->update([
                'verified' => true,
                'verified_at' => now(),
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Backup verification failed for backup {$backupLog->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restore backup
     */
    public function restoreBackup(BackupLog $backupLog, array $options = []): bool
    {
        try {
            if ($backupLog->status !== 'completed') {
                throw new \Exception("Cannot restore incomplete backup");
            }
            
            $storageDriver = $backupLog->storage_driver;
            $disk = $storageDriver === 's3' ? Storage::disk('s3') : Storage::disk('private');
            
            if (!$disk->exists($backupLog->file_path)) {
                throw new \Exception("Backup file not found");
            }
            
            // Download backup to temp location
            $tempFile = tempnam(sys_get_temp_dir(), 'backup_restore_');
            file_put_contents($tempFile, $disk->get($backupLog->file_path));
            
            // Decrypt if encrypted
            if ($backupLog->is_encrypted) {
                $decryptedData = Crypt::decrypt(file_get_contents($tempFile));
                file_put_contents($tempFile, $decryptedData);
            }
            
            // Extract and restore based on backup type
            switch ($backupLog->backup_type) {
                case 'database':
                    $this->restoreDatabase($tempFile, $backupLog);
                    break;
                case 'full':
                    $this->restoreFullBackup($tempFile, $backupLog, $options);
                    break;
                default:
                    throw new \Exception("Unsupported backup type for restore: {$backupLog->backup_type}");
            }
            
            unlink($tempFile);
            
            Log::info("Backup restored successfully", ['backup_id' => $backupLog->id]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error("Backup restore failed for backup {$backupLog->id}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Restore database from backup
     */
    protected function restoreDatabase(string $backupFile, BackupLog $backupLog): void
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");
        
        // Decompress if needed
        if ($backupLog->is_compressed) {
            $decompressedFile = tempnam(sys_get_temp_dir(), 'backup_decompress_');
            $this->decompressFile($backupFile, $decompressedFile);
            $backupFile = $decompressedFile;
        }
        
        switch ($dbConfig['driver']) {
            case 'mysql':
                $this->restoreMysql($dbConfig, $backupFile);
                break;
            case 'pgsql':
                $this->restorePostgres($dbConfig, $backupFile);
                break;
            case 'sqlite':
                $this->restoreSqlite($dbConfig, $backupFile);
                break;
            default:
                throw new \Exception("Unsupported database driver: {$dbConfig['driver']}");
        }
        
        if (isset($decompressedFile)) {
            unlink($decompressedFile);
        }
    }

    /**
     * Restore MySQL database
     */
    protected function restoreMysql(array $config, string $backupFile): void
    {
        $command = sprintf(
            'mysql --host=%s --port=%s --user=%s --password=%s %s < %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['password']),
            escapeshellarg($config['database']),
            escapeshellarg($backupFile)
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("MySQL restore failed with return code: {$returnCode}");
        }
    }

    /**
     * Restore PostgreSQL database
     */
    protected function restorePostgres(array $config, string $backupFile): void
    {
        $env = [
            'PGPASSWORD' => $config['password']
        ];
        
        $command = sprintf(
            'pg_restore --host=%s --port=%s --username=%s --dbname=%s --no-password --clean --if-exists %s',
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($backupFile)
        );
        
        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ], $pipes, null, $env);
        
        if (!is_resource($process)) {
            throw new \Exception("Failed to start PostgreSQL restore process");
        }
        
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        $returnCode = proc_close($process);
        
        if ($returnCode !== 0) {
            Log::warning("PostgreSQL restore completed with warnings: {$error}");
        }
    }

    /**
     * Restore SQLite database
     */
    protected function restoreSqlite(array $config, string $backupFile): void
    {
        $dbPath = $config['database'];
        
        // Backup current database
        $currentBackup = $dbPath . '.restore_backup_' . time();
        if (file_exists($dbPath)) {
            copy($dbPath, $currentBackup);
        }
        
        // Restore from backup
        if (!copy($backupFile, $dbPath)) {
            // Restore original if copy failed
            if (file_exists($currentBackup)) {
                copy($currentBackup, $dbPath);
                unlink($currentBackup);
            }
            throw new \Exception("Failed to restore SQLite database file");
        }
        
        // Clean up temporary backup
        if (file_exists($currentBackup)) {
            unlink($currentBackup);
        }
    }

    /**
     * Restore full backup
     */
    protected function restoreFullBackup(string $backupFile, BackupLog $backupLog, array $options = []): void
    {
        $tempDir = sys_get_temp_dir() . '/opengrc_restore_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Extract backup archive
            $zip = new ZipArchive();
            if ($zip->open($backupFile) !== TRUE) {
                throw new \Exception("Cannot open backup archive");
            }
            
            $zip->extractTo($tempDir);
            $zip->close();
            
            // Restore database if requested
            if ($options['restore_database'] ?? true) {
                $dbBackupFiles = glob($tempDir . '/database/*');
                if (!empty($dbBackupFiles)) {
                    $this->restoreDatabase($dbBackupFiles[0], $backupLog);
                }
            }
            
            // Restore files if requested
            if ($options['restore_files'] ?? false) {
                $filesBackupFiles = glob($tempDir . '/files/*');
                if (!empty($filesBackupFiles)) {
                    $this->restoreFiles($filesBackupFiles[0], $options);
                }
            }
            
        } finally {
            // Cleanup
            $this->removeDirectory($tempDir);
        }
    }

    /**
     * Restore files from backup
     */
    protected function restoreFiles(string $filesBackupPath, array $options = []): void
    {
        $tempDir = sys_get_temp_dir() . '/opengrc_files_restore_' . uniqid();
        mkdir($tempDir, 0755, true);
        
        try {
            // Extract files backup
            $zip = new ZipArchive();
            if ($zip->open($filesBackupPath) !== TRUE) {
                throw new \Exception("Cannot open files backup archive");
            }
            
            $zip->extractTo($tempDir);
            $zip->close();
            
            // Restore files to their original locations
            $this->copyDirectoryContents($tempDir, base_path(), $options['overwrite'] ?? false);
            
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    /**
     * Copy directory contents
     */
    protected function copyDirectoryContents(string $source, string $destination, bool $overwrite = false): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                if ($overwrite || !file_exists($target)) {
                    copy($item, $target);
                }
            }
        }
    }

    /**
     * Decompress gzip file
     */
    protected function decompressFile(string $source, string $destination): void
    {
        $sourceHandle = gzopen($source, 'rb');
        $destHandle = fopen($destination, 'wb');
        
        if (!$sourceHandle || !$destHandle) {
            throw new \Exception("Failed to open files for decompression");
        }
        
        while (!gzeof($sourceHandle)) {
            fwrite($destHandle, gzread($sourceHandle, 8192));
        }
        
        gzclose($sourceHandle);
        fclose($destHandle);
    }

    /**
     * Clean up expired backups
     */
    public function cleanupExpiredBackups(): int
    {
        $expiredBackups = BackupLog::expired()->get();
        $cleanedCount = 0;
        
        foreach ($expiredBackups as $backup) {
            try {
                $storageDriver = $backup->storage_driver;
                $disk = $storageDriver === 's3' ? Storage::disk('s3') : Storage::disk('private');
                
                if ($disk->exists($backup->file_path)) {
                    $disk->delete($backup->file_path);
                }
                
                $backup->delete();
                $cleanedCount++;
                
            } catch (\Exception $e) {
                Log::error("Failed to cleanup expired backup {$backup->id}: " . $e->getMessage());
            }
        }
        
        return $cleanedCount;
    }

    /**
     * Create backup log entry
     */
    protected function createBackupLog(string $type, array $config = []): BackupLog
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = $config['name'] ?? "backup_{$type}_{$timestamp}";
        
        $retentionDays = $config['retention_days'] ?? setting('backup.retention_days', 30);
        $expiresAt = $retentionDays > 0 ? now()->addDays($retentionDays) : null;
        
        return BackupLog::create([
            'backup_name' => $backupName,
            'backup_type' => $type,
            'status' => 'pending',
            'storage_driver' => $config['storage_driver'] ?? setting('storage.driver', 'private'),
            'backup_config' => $config,
            'included_tables' => $config['include_tables'] ?? [],
            'excluded_tables' => $config['exclude_tables'] ?? ['backup_logs', 'failed_jobs', 'sessions'],
            'is_encrypted' => $config['encrypt'] ?? false,
            'is_compressed' => $config['compress'] ?? true,
            'expires_at' => $expiresAt,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Remove directory recursively
     */
    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }

    /**
     * Cleanup temporary files
     */
    protected function cleanup(): void
    {
        if (is_dir($this->tempPath)) {
            $this->removeDirectory($this->tempPath);
        }
    }
} 