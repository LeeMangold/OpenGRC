<?php

namespace App\Filament\Resources\BackupLogResource\Pages;

use App\Filament\Resources\BackupLogResource;
use App\Services\BackupService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;

class ListBackupLogs extends ListRecords
{
    protected static string $resource = BackupLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create_database_backup')
                ->label('Create Database Backup')
                ->icon('heroicon-o-circle-stack')
                ->color('success')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('Backup Name')
                        ->placeholder('Leave empty for auto-generated name'),
                    
                    Forms\Components\Toggle::make('encrypt')
                        ->label('Encrypt Backup')
                        ->default(false),
                    
                    Forms\Components\Toggle::make('compress')
                        ->label('Compress Backup')
                        ->default(true),
                    
                    Forms\Components\Select::make('storage_driver')
                        ->label('Storage Driver')
                        ->options([
                            'private' => 'Private Local Storage',
                            'local' => 'Local Storage',
                            's3' => 'Amazon S3',
                        ])
                        ->default(setting('storage.driver', 'private')),
                    
                    Forms\Components\TextInput::make('retention_days')
                        ->label('Retention Days')
                        ->numeric()
                        ->default(30)
                        ->minValue(1),
                    
                    Forms\Components\TagsInput::make('exclude_tables')
                        ->label('Exclude Tables')
                        ->default(['backup_logs', 'failed_jobs', 'sessions']),
                ])
                ->action(function (array $data) {
                    $backupService = app(BackupService::class);
                    
                    try {
                        $config = [
                            'name' => $data['name'],
                            'encrypt' => $data['encrypt'],
                            'compress' => $data['compress'],
                            'storage_driver' => $data['storage_driver'],
                            'retention_days' => $data['retention_days'],
                            'exclude_tables' => $data['exclude_tables'],
                        ];
                        
                        $backupLog = $backupService->createDatabaseBackup($config);
                        
                        Notification::make()
                            ->title('Database Backup Started')
                            ->body("Backup '{$backupLog->backup_name}' has been started.")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Backup Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('create_full_backup')
                ->label('Create Full Backup')
                ->icon('heroicon-o-server-stack')
                ->color('primary')
                ->form([
                    Forms\Components\TextInput::make('name')
                        ->label('Backup Name')
                        ->placeholder('Leave empty for auto-generated name'),
                    
                    Forms\Components\Toggle::make('encrypt')
                        ->label('Encrypt Backup')
                        ->default(false),
                    
                    Forms\Components\Toggle::make('compress')
                        ->label('Compress Backup')
                        ->default(true),
                    
                    Forms\Components\Select::make('storage_driver')
                        ->label('Storage Driver')
                        ->options([
                            'private' => 'Private Local Storage',
                            'local' => 'Local Storage',
                            's3' => 'Amazon S3',
                        ])
                        ->default(setting('storage.driver', 'private')),
                    
                    Forms\Components\TextInput::make('retention_days')
                        ->label('Retention Days')
                        ->numeric()
                        ->default(30)
                        ->minValue(1),
                    
                    Forms\Components\TagsInput::make('exclude_tables')
                        ->label('Exclude Tables')
                        ->default(['backup_logs', 'failed_jobs', 'sessions']),
                    
                    Forms\Components\TagsInput::make('backup_directories')
                        ->label('Backup Directories')
                        ->default(['storage/app/private', 'storage/app/public', '.env']),
                    
                    Forms\Components\TagsInput::make('exclude_patterns')
                        ->label('Exclude File Patterns')
                        ->default(['*.log', 'storage/framework/cache/*', 'node_modules/*']),
                ])
                ->action(function (array $data) {
                    $backupService = app(BackupService::class);
                    
                    try {
                        $config = [
                            'name' => $data['name'],
                            'encrypt' => $data['encrypt'],
                            'compress' => $data['compress'],
                            'storage_driver' => $data['storage_driver'],
                            'retention_days' => $data['retention_days'],
                            'exclude_tables' => $data['exclude_tables'],
                            'backup_directories' => $data['backup_directories'],
                            'exclude_patterns' => $data['exclude_patterns'],
                        ];
                        
                        $backupLog = $backupService->createFullBackup($config);
                        
                        Notification::make()
                            ->title('Full Backup Started')
                            ->body("Backup '{$backupLog->backup_name}' has been started.")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Backup Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('cleanup_expired')
                ->label('Cleanup Expired')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->action(function () {
                    $backupService = app(BackupService::class);
                    
                    try {
                        $cleaned = $backupService->cleanupExpiredBackups();
                        
                        Notification::make()
                            ->title('Cleanup Complete')
                            ->body("Cleaned up {$cleaned} expired backup(s).")
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Cleanup Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),
        ];
    }
} 