<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BackupLogResource\Pages;
use App\Models\BackupLog;
use App\Services\BackupService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class BackupLogResource extends Resource
{
    protected static ?string $model = BackupLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Backup Logs';

    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Backup Information')
                    ->schema([
                        Forms\Components\TextInput::make('backup_name')
                            ->label('Backup Name')
                            ->disabled(),
                        
                        Forms\Components\Select::make('backup_type')
                            ->label('Backup Type')
                            ->options([
                                'full' => 'Full Backup',
                                'database' => 'Database Only',
                                'files' => 'Files Only',
                                'incremental' => 'Incremental',
                            ])
                            ->disabled(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'running' => 'Running',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('storage_driver')
                            ->label('Storage Driver')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('File Information')
                    ->schema([
                        Forms\Components\TextInput::make('file_name')
                            ->label('File Name')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('file_size')
                            ->label('File Size (bytes)')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('checksum')
                            ->label('Checksum')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Timing Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Started At')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Completed At')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('duration')
                            ->label('Duration (seconds)')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\KeyValue::make('backup_config')
                            ->label('Backup Configuration')
                            ->disabled(),
                        
                        Forms\Components\TagsInput::make('included_tables')
                            ->label('Included Tables')
                            ->disabled(),
                        
                        Forms\Components\TagsInput::make('excluded_tables')
                            ->label('Excluded Tables')
                            ->disabled(),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('error_message')
                            ->label('Error Message')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->status === 'failed'),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('backup_name')
                    ->label('Backup Name')
                    ->sortable()
                    ->searchable()
                    ->limit(30),

                BadgeColumn::make('backup_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'full',
                        'success' => 'database',
                        'warning' => 'files',
                        'info' => 'incremental',
                    ])
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'pending',
                        'blue' => 'running',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'warning' => 'cancelled',
                    ])
                    ->sortable(),

                TextColumn::make('formatted_size')
                    ->label('Size')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('file_size', $direction);
                    }),

                BadgeColumn::make('storage_driver')
                    ->label('Storage')
                    ->colors([
                        'primary' => 'private',
                        'success' => 'local',
                        'info' => 's3',
                    ])
                    ->sortable(),

                IconColumn::make('is_encrypted')
                    ->label('Encrypted')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_compressed')
                    ->label('Compressed')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('verified')
                    ->label('Verified')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? $state . 's' : 'N/A')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : null),

                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('backup_type')
                    ->options([
                        'full' => 'Full Backup',
                        'database' => 'Database Only',
                        'files' => 'Files Only',
                        'incremental' => 'Incremental',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('storage_driver')
                    ->options([
                        'local' => 'Local',
                        'private' => 'Private',
                        's3' => 'Amazon S3',
                    ]),

                Filter::make('encrypted')
                    ->query(fn (Builder $query): Builder => $query->where('is_encrypted', true))
                    ->label('Encrypted Only'),

                Filter::make('verified')
                    ->query(fn (Builder $query): Builder => $query->where('verified', true))
                    ->label('Verified Only'),

                Filter::make('expired')
                    ->query(fn (Builder $query): Builder => $query->expired())
                    ->label('Expired Backups'),

                Filter::make('recent')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(7)))
                    ->label('Last 7 Days'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('verify')
                        ->label('Verify')
                        ->icon('heroicon-o-shield-check')
                        ->color('success')
                        ->visible(fn ($record) => $record->status === 'completed')
                        ->action(function ($record) {
                            $backupService = app(BackupService::class);
                            
                            try {
                                if ($backupService->verifyBackup($record)) {
                                    Notification::make()
                                        ->title('Backup Verified')
                                        ->body("Backup {$record->backup_name} has been verified successfully.")
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Verification Failed')
                                        ->body("Backup {$record->backup_name} verification failed.")
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
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->visible(fn ($record) => $record->status === 'completed')
                        ->url(function ($record) {
                            $disk = $record->storage_driver === 's3' ? Storage::disk('s3') : Storage::disk('private');
                            
                            if ($disk->exists($record->file_path)) {
                                return $disk->temporaryUrl($record->file_path, now()->addMinutes(30));
                            }
                            
                            return null;
                        })
                        ->openUrlInNewTab(),

                    Action::make('restore')
                        ->label('Restore')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn ($record) => $record->status === 'completed' && $record->verified)
                        ->form([
                            Forms\Components\Checkbox::make('restore_database')
                                ->label('Restore Database')
                                ->default(true),
                            
                            Forms\Components\Checkbox::make('restore_files')
                                ->label('Restore Files')
                                ->default(false)
                                ->visible(fn ($record) => $record->backup_type === 'full'),
                            
                            Forms\Components\Checkbox::make('overwrite_files')
                                ->label('Overwrite Existing Files')
                                ->default(false),
                        ])
                        ->action(function ($record, array $data) {
                            $backupService = app(BackupService::class);
                            
                            try {
                                if ($backupService->restoreBackup($record, $data)) {
                                    Notification::make()
                                        ->title('Restore Completed')
                                        ->body("Backup {$record->backup_name} has been restored successfully.")
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Restore Failed')
                                        ->body("Failed to restore backup {$record->backup_name}.")
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

                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    DeleteAction::make()
                        ->action(function ($record) {
                            // Delete the backup file
                            $disk = $record->storage_driver === 's3' ? Storage::disk('s3') : Storage::disk('private');
                            
                            if ($record->file_path && $disk->exists($record->file_path)) {
                                $disk->delete($record->file_path);
                            }
                            
                            // Delete the record
                            $record->delete();
                            
                            Notification::make()
                                ->title('Backup Deleted')
                                ->body("Backup {$record->backup_name} has been deleted.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Action::make('verify_selected')
                        ->label('Verify Selected')
                        ->icon('heroicon-o-shield-check')
                        ->color('success')
                        ->action(function ($records) {
                            $backupService = app(BackupService::class);
                            $verified = 0;
                            $failed = 0;
                            
                            foreach ($records as $record) {
                                if ($record->status === 'completed') {
                                    try {
                                        if ($backupService->verifyBackup($record)) {
                                            $verified++;
                                        } else {
                                            $failed++;
                                        }
                                    } catch (\Exception $e) {
                                        $failed++;
                                    }
                                }
                            }
                            
                            Notification::make()
                                ->title('Bulk Verification Complete')
                                ->body("Verified: {$verified}, Failed: {$failed}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $disk = $record->storage_driver === 's3' ? Storage::disk('s3') : Storage::disk('private');
                                
                                if ($record->file_path && $disk->exists($record->file_path)) {
                                    $disk->delete($record->file_path);
                                }
                            }
                            
                            $count = $records->count();
                            $records->each->delete();
                            
                            Notification::make()
                                ->title('Backups Deleted')
                                ->body("{$count} backup(s) have been deleted.")
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackupLogs::route('/'),
            'create' => Pages\CreateBackupLog::route('/create'),
            'view' => Pages\ViewBackupLog::route('/{record}'),
            'edit' => Pages\EditBackupLog::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'running')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'running')->count() > 0 ? 'primary' : null;
    }
} 