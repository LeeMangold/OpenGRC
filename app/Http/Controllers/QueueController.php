<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class QueueController extends Controller
{
    /**
     * Check if a queue worker is currently running
     */
    public function isQueueWorkerRunning(): bool
    {
        // Check for running queue worker processes using ps command (more reliable)
        $process = new Process(['ps', 'aux']);
        $process->run();
        
        if (!$process->isSuccessful()) {
            return false;
        }
        
        $output = $process->getOutput();
        
        // Look for queue:work processes that are not defunct
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            if (strpos($line, 'queue:work') !== false && strpos($line, '<defunct>') === false) {
                Log::info('Found running queue worker', ['process_line' => $line]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Start a queue worker in the background
     */
    public function startQueueWorker(): void
    {
        // Use shell command to start queue worker as a true background process
        $command = sprintf(
            'nohup %s %s queue:work --daemon --tries=3 --timeout=300 > /dev/null 2>&1 &',
            PHP_BINARY,
            base_path('artisan')
        );
        
        // Execute the command in the background
        exec($command);
        
        Log::info('Queue worker started in background', [
            'command' => $command,
            'timestamp' => now()
        ]);
    }

    /**
     * Ensure a queue worker is running, start one if needed
     */
    public function ensureQueueWorkerRunning(): bool
    {
        if (!$this->isQueueWorkerRunning()) {
            $this->startQueueWorker();
            return false; // Worker was not running, started a new one
        }
        
        return true; // Worker was already running
    }

    /**
     * Get queue worker status information
     */
    public function getQueueWorkerStatus(): array
    {
        $isRunning = $this->isQueueWorkerRunning();
        
        return [
            'is_running' => $isRunning,
            'status' => $isRunning ? 'running' : 'stopped',
            'checked_at' => now(),
        ];
    }

    /**
     * Stop all queue workers (if needed for maintenance)
     */
    public function stopQueueWorkers(): void
    {
        // Find and kill queue worker processes
        $process = new Process(['pkill', '-f', 'queue:work']);
        $process->run();
        
        Log::info('Queue workers stop command executed', [
            'success' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'timestamp' => now()
        ]);
    }
}