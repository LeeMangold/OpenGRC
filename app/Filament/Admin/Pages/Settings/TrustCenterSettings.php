<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\TrustCenterMailSchema;
use App\Filament\Admin\Pages\Settings\Schemas\TrustCenterNdaSchema;
use Closure;
use Filament\Forms\Components\Tabs;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class TrustCenterSettings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

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
        return 'Trust Center';
    }

    public function getTitle(): string
    {
        return 'Trust Center Settings';
    }

    public function schema(): array|Closure
    {
        return [
            Tabs::make('TrustCenterSettings')
                ->tabs([
                    Tabs\Tab::make(__('NDA'))
                        ->icon('heroicon-o-document-text')
                        ->schema(TrustCenterNdaSchema::schema()),
                    Tabs\Tab::make(__('Email Templates'))
                        ->icon('heroicon-o-envelope')
                        ->schema(TrustCenterMailSchema::schema()),
                ]),
        ];
    }
}
