<?php

namespace App\Filament\Admin\Pages\Settings\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;

class BackupSchema
{
    public static function schema(): array
    {
        return [
            Section::make('Backup Configuration')
                ->description('Configure automated backup settings and retention policies')
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('backup.enabled')
                            ->label('Enable Automated Backups')
                            ->default(true)
                            ->helperText('Enable or disable automated backup system'),

                        Select::make('backup.default_type')
                            ->label('Default Backup Type')
                            ->options([
                                'database' => 'Database Only',
                                'full' => 'Full Backup (Database + Files)',
                            ])
                            ->default('database')
                            ->helperText('Type of backup to create by default'),
                    ]),

                    Grid::make(3)->schema([
                        TextInput::make('backup.retention_days')
                            ->label('Retention Period (Days)')
                            ->numeric()
                            ->default(30)
                            ->minValue(1)
                            ->maxValue(365)
                            ->helperText('How long to keep backups before deletion'),

                        TextInput::make('backup.max_backups')
                            ->label('Maximum Backups')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Maximum number of backups to keep'),

                        Select::make('backup.schedule')
                            ->label('Backup Schedule')
                            ->options([
                                'disabled' => 'Disabled',
                                'hourly' => 'Every Hour',
                                'daily' => 'Daily at 2 AM',
                                'weekly' => 'Weekly (Sunday 2 AM)',
                                'monthly' => 'Monthly (1st day 2 AM)',
                            ])
                            ->default('daily')
                            ->helperText('Automated backup frequency'),
                    ]),
                ]),

            Section::make('Backup Options')
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('backup.compress')
                            ->label('Enable Compression')
                            ->default(true)
                            ->helperText('Compress backups to save storage space'),

                        Toggle::make('backup.encrypt')
                            ->label('Enable Encryption')
                            ->default(false)
                            ->helperText('Encrypt backups for additional security'),
                    ]),

                    TagsInput::make('backup.exclude_tables')
                        ->label('Exclude Tables')
                        ->placeholder('Add table name...')
                        ->default(['backup_logs', 'failed_jobs', 'sessions', 'cache'])
                        ->helperText('Database tables to exclude from backups'),

                    TagsInput::make('backup.backup_directories')
                        ->label('Backup Directories')
                        ->placeholder('Add directory path...')
                        ->default(['storage/app/private', 'storage/app/public', '.env'])
                        ->helperText('Directories to include in full backups (relative to app root)'),

                    TagsInput::make('backup.exclude_patterns')
                        ->label('Exclude File Patterns')
                        ->placeholder('Add pattern...')
                        ->default(['*.log', 'storage/framework/cache/*', 'storage/framework/sessions/*', 'node_modules/*'])
                        ->helperText('File patterns to exclude from backups (supports wildcards)'),
                ]),

            Section::make('Storage Configuration')
                ->schema([
                    Select::make('backup.storage_driver')
                        ->label('Backup Storage Driver')
                        ->options([
                            'local' => 'Local Storage',
                            'private' => 'Private Local Storage',
                            's3' => 'Amazon S3',
                        ])
                        ->default('private')
                        ->live()
                        ->helperText('Where to store backup files'),

                    TextInput::make('backup.local_path')
                        ->label('Local Backup Path')
                        ->default('backups')
                        ->visible(fn ($get) => in_array($get('backup.storage_driver'), ['local', 'private']))
                        ->helperText('Directory path for local backups (relative to storage/app)'),
                ]),

            Section::make('Notifications')
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('backup.notify_on_success')
                            ->label('Notify on Successful Backups')
                            ->default(false)
                            ->helperText('Send notifications when backups complete successfully'),

                        Toggle::make('backup.notify_on_failure')
                            ->label('Notify on Failed Backups')
                            ->default(true)
                            ->helperText('Send notifications when backups fail'),
                    ]),

                    Textarea::make('backup.notification_emails')
                        ->label('Notification Email Addresses')
                        ->placeholder('admin@example.com, backup@example.com')
                        ->helperText('Comma-separated email addresses for backup notifications'),
                ]),

            Section::make('Verification & Monitoring')
                ->schema([
                    Grid::make(2)->schema([
                        Toggle::make('backup.auto_verify')
                            ->label('Auto-Verify Backups')
                            ->default(true)
                            ->helperText('Automatically verify backup integrity after creation'),

                        Select::make('backup.verification_schedule')
                            ->label('Verification Schedule')
                            ->options([
                                'disabled' => 'Disabled',
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('weekly')
                            ->helperText('How often to verify existing backups'),
                    ]),

                    Grid::make(2)->schema([
                        Toggle::make('backup.auto_cleanup')
                            ->label('Auto-Cleanup Expired Backups')
                            ->default(true)
                            ->helperText('Automatically delete expired backups'),

                        Select::make('backup.cleanup_schedule')
                            ->label('Cleanup Schedule')
                            ->options([
                                'disabled' => 'Disabled',
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('daily')
                            ->helperText('How often to clean up expired backups'),
                    ]),
                ]),

            Section::make('Disaster Recovery')
                ->schema([
                    Textarea::make('backup.disaster_recovery_contacts')
                        ->label('Emergency Contact Information')
                        ->placeholder('Name: John Doe, Phone: +1-555-0123, Email: john@example.com')
                        ->helperText('Contact information for disaster recovery situations'),

                    Textarea::make('backup.recovery_procedures')
                        ->label('Recovery Procedures')
                        ->placeholder('1. Identify the backup to restore...')
                        ->helperText('Step-by-step procedures for disaster recovery'),

                    TextInput::make('backup.rpo_target')
                        ->label('Recovery Point Objective (RPO)')
                        ->placeholder('e.g., 1 hour, 4 hours, 1 day')
                        ->helperText('Maximum acceptable data loss in time'),

                    TextInput::make('backup.rto_target')
                        ->label('Recovery Time Objective (RTO)')
                        ->placeholder('e.g., 30 minutes, 2 hours, 4 hours')
                        ->helperText('Maximum acceptable downtime for recovery'),
                ]),
        ];
    }
} 