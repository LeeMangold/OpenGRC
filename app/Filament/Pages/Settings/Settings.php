<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Pages\Settings\Schemas\AiSchema;
use App\Filament\Pages\Settings\Schemas\AuthenticationSchema;
use App\Filament\Pages\Settings\Schemas\GeneralSchema;
use App\Filament\Pages\Settings\Schemas\MailSchema;
use App\Filament\Pages\Settings\Schemas\MailTemplatesSchema;
use App\Filament\Pages\Settings\Schemas\ReportSchema;
use App\Filament\Pages\Settings\Schemas\SecuritySchema;
use Closure;
use Filament\Forms\Components\Tabs;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class Settings extends BaseSettings
{
    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        if (auth()->user()->can('Manage Preferences')) {
            return true;
        }

        return false;
    }

    public static function getNavigationLabel(): string
    {
        return 'General Settings';
    }

    public function schema(): array|Closure
    {
        return [
            Tabs::make('Settings')
                ->columns(2)
                ->schema([
                    Tabs\Tab::make('General')
                        ->schema(GeneralSchema::schema()),
                    Tabs\Tab::make('Mail')
                        ->columns(3)
                        ->label('Mail Settings')
                        ->schema(MailSchema::schema()),
                    Tabs\Tab::make('Mail Templates')
                        ->schema(MailTemplatesSchema::schema()),
                    Tabs\Tab::make('AI Settings')
                        ->schema(AiSchema::schema()),
                    Tabs\Tab::make('Report Settings')
                        ->schema(ReportSchema::schema()),
                    Tabs\Tab::make('Security')
                        ->schema(SecuritySchema::schema()),
                    Tabs\Tab::make('Authentication')
                        ->schema(AuthenticationSchema::schema()),
                ]),
        ];
    }
}
