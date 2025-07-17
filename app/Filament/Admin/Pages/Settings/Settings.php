<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\AiSchema;
use App\Filament\Admin\Pages\Settings\Schemas\AuthenticationSchema;
use App\Filament\Admin\Pages\Settings\Schemas\GeneralSchema;
use App\Filament\Admin\Pages\Settings\Schemas\MailSchema;
use App\Filament\Admin\Pages\Settings\Schemas\MailTemplatesSchema;
use App\Filament\Admin\Pages\Settings\Schemas\ReportSchema;
use App\Filament\Admin\Pages\Settings\Schemas\SecuritySchema;
use Closure;
use Filament\Forms\Components\Tabs;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class Settings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        if (auth()->user()->can('Manage Preferences')) {
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
                    Tabs\Tab::make(__('navigation.settings.tabs.authentication'))
                        ->schema(AuthenticationSchema::schema()),
                ]),
        ];
    }
}
