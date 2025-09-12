<?php

namespace App\Filament\Resources\BackupLogResource\Pages;

use App\Filament\Resources\BackupLogResource;
use App\Services\BackupService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ViewBackupLog extends ViewRecord
{
    protected static string $resource = BackupLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify')
                ->label('Verify Backup')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->visible(fn () => $this->record->status === 'completed')
                ->action(function () {
                    $backupService = app(BackupService::class);
                    
                    try {
                        if ($backupService->verifyBackup($this->record)) {
                            Notification::make()
                                ->title('Backup Verified')
                                ->body("Backup has been verified successfully.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Verification Failed')
                                ->body("Backup verification failed.")
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Verification Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),

            Action::make('download')
                ->label('Download Backup')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'completed')
                ->url(function () {
                    $disk = $this->record->storage_driver === 's3' ? Storage::disk('s3') : Storage::disk('private');
                    
                    if ($disk->exists($this->record->file_path)) {
                        return $disk->temporaryUrl($this->record->file_path, now()->addMinutes(30));
                    }
                    
                    return null;
                })
                ->openUrlInNewTab(),

            Action::make('restore')
                ->label('Restore Backup')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'completed' && $this->record->verified)
                ->form([
                    Forms\Components\Checkbox::make('restore_database')
                        ->label('Restore Database')
                        ->default(true),
                    
                    Forms\Components\Checkbox::make('restore_files')
                        ->label('Restore Files')
                        ->default(false)
                        ->visible(fn () => $this->record->backup_type === 'full'),
                    
                    Forms\Components\Checkbox::make('overwrite_files')
                        ->label('Overwrite Existing Files')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    $backupService = app(BackupService::class);
                    
                    try {
                        if ($backupService->restoreBackup($this->record, $data)) {
                            Notification::make()
                                ->title('Restore Completed')
                                ->body("Backup has been restored successfully.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Restore Failed')
                                ->body("Failed to restore backup.")
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Restore Error')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalDescription('This will overwrite existing data. Make sure you have a recent backup before proceeding.'),

            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->action(function () {
                    // Delete the backup file
                    $disk = $this->record->storage_driver === 's3' ? Storage::disk('s3') : Storage::disk('private');
                    
                    if ($this->record->file_path && $disk->exists($this->record->file_path)) {
                        $disk->delete($this->record->file_path);
                    }
                    
                    // Delete the record
                    $this->record->delete();
                    
                    Notification::make()
                        ->title('Backup Deleted')
                        ->body("Backup has been deleted.")
                        ->success()
                        ->send();
                        
                    return redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
} 