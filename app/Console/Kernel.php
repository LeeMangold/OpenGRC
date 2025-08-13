<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Backup scheduling based on settings
        if (setting('backup.enabled', true)) {
            $backupSchedule = setting('backup.schedule', 'daily');
            $backupType = setting('backup.default_type', 'database');
            
            switch ($backupSchedule) {
                case 'hourly':
                    $schedule->command("backup:{$backupType}")->hourly();
                    break;
                case 'daily':
                    $schedule->command("backup:{$backupType}")->dailyAt('02:00');
                    break;
                case 'weekly':
                    $schedule->command("backup:{$backupType}")->weeklyOn(0, '02:00');
                    break;
                case 'monthly':
                    $schedule->command("backup:{$backupType}")->monthlyOn(1, '02:00');
                    break;
            }
        }
        
        // Backup verification scheduling
        if (setting('backup.auto_verify', true)) {
            $verificationSchedule = setting('backup.verification_schedule', 'weekly');
            
            switch ($verificationSchedule) {
                case 'daily':
                    $schedule->command('backup:verify --all')->dailyAt('03:00');
                    break;
                case 'weekly':
                    $schedule->command('backup:verify --all')->weeklyOn(1, '03:00');
                    break;
                case 'monthly':
                    $schedule->command('backup:verify --all')->monthlyOn(2, '03:00');
                    break;
            }
        }
        
        // Backup cleanup scheduling
        if (setting('backup.auto_cleanup', true)) {
            $cleanupSchedule = setting('backup.cleanup_schedule', 'daily');
            
            switch ($cleanupSchedule) {
                case 'daily':
                    $schedule->command('backup:cleanup --force')->dailyAt('04:00');
                    break;
                case 'weekly':
                    $schedule->command('backup:cleanup --force')->weeklyOn(1, '04:00');
                    break;
                case 'monthly':
                    $schedule->command('backup:cleanup --force')->monthlyOn(3, '04:00');
                    break;
            }
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
