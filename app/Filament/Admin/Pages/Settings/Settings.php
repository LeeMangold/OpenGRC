<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\AiSchema;
use App\Filament\Admin\Pages\Settings\Schemas\AuthenticationSchema;
use App\Filament\Admin\Pages\Settings\Schemas\BackupSchema;
use App\Filament\Admin\Pages\Settings\Schemas\GeneralSchema;
use App\Filament\Admin\Pages\Settings\Schemas\MailSchema;
use App\Filament\Admin\Pages\Settings\Schemas\MailTemplatesSchema;
use App\Filament\Admin\Pages\Settings\Schemas\ReportSchema;
use App\Filament\Admin\Pages\Settings\Schemas\SecuritySchema;
use App\Filament\Admin\Pages\Settings\Schemas\StorageSchema;
use Closure;
use Filament\Forms\Components\Tabs;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class Settings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        if (auth()->check() && auth()->user()->can('Manage Preferences')) {
            return true;
        }

        return false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.settings.general_settings');
    }

    public function schema(): array|Closure
    {
        return [
            Tabs::make('Settings')
                ->columns(2)
                ->schema([
                    Tabs\Tab::make(__('navigation.settings.tabs.general'))
                        ->schema(GeneralSchema::schema()),
                    Tabs\Tab::make(__('navigation.settings.tabs.storage'))
                        ->schema(StorageSchema::schema()),
                    Tabs\Tab::make(__('navigation.settings.tabs.mail'))
                        ->columns(3)
                        ->schema(MailSchema::schema()),
                    Tabs\Tab::make(__('navigation.settings.tabs.mail_templates'))
                        ->schema(MailTemplatesSchema::schema()),
                    Tabs\Tab::make(__('navigation.settings.tabs.ai'))
                        ->schema(AiSchema::schema()),
                    Tabs\Tab::make(__('navigation.settings.tabs.report'))
                        ->schema(ReportSchema::schema()),
                    Tabs\Tab::make(__('navigation.settings.tabs.security'))
                        ->schema(SecuritySchema::schema()),
                    Tabs\Tab::make('Backup & Recovery')
                        ->schema(BackupSchema::schema()),
                    Tabs\Tab::make(__('navigation.settings.tabs.authentication'))
                        ->schema(AuthenticationSchema::schema()),
                ]),
        ];
    }

    protected function afterSave(): void
    {
        $driver = setting('storage.driver');

        try {
            // Update environment variables based on the selected storage driver
            if ($driver === 'digitalocean') {
                \Log::info('About to update DigitalOcean environment variables after save');
                \App\Filament\Admin\Pages\Settings\Schemas\StorageSchema::updateDigitalOceanEnvVars();
                \Log::info('Successfully updated DigitalOcean environment variables after save');
            } else {
                // For any other driver (private, s3), clear DigitalOcean env vars and set FILESYSTEM_DISK
                \Log::info('Clearing DigitalOcean environment variables for driver: '.$driver);
                \App\Filament\Admin\Pages\Settings\Schemas\StorageSchema::clearDigitalOceanEnvVars();

                // Update the FILESYSTEM_DISK environment variable to match the selected driver
                \App\Filament\Admin\Pages\Settings\Schemas\StorageSchema::updateFilesystemDisk($driver);
                \Log::info('Successfully updated environment variables for driver: '.$driver);
            }
        } catch (\Throwable $e) {
            \Log::error('Failed to update environment variables after save: '.$e->getMessage());
            \Log::error('Exception trace: '.$e->getTraceAsString());

            // Don't break the save process, just log the error
            // The user's settings will still be saved
        }
    }
}
