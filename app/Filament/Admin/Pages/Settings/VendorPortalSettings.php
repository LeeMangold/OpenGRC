<?php

namespace App\Filament\Admin\Pages\Settings;

use App\Filament\Admin\Pages\Settings\Schemas\RiskScoringSchema;
use App\Filament\Admin\Pages\Settings\Schemas\SurveySettingsSchema;
use App\Filament\Admin\Pages\Settings\Schemas\VendorPortalMailSchema;
use App\Filament\Admin\Pages\Settings\Schemas\VendorPortalSchema;
use Closure;
use Filament\Forms\Components\Tabs;
use Outerweb\FilamentSettings\Filament\Pages\Settings as BaseSettings;

class VendorPortalSettings extends BaseSettings
{
    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

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
        return 'Vendor Portal';
    }

    public function getTitle(): string
    {
        return 'Vendor Portal Settings';
    }

    public function schema(): array|Closure
    {
        return [
            Tabs::make('VendorPortalSettings')
                ->tabs([
                    Tabs\Tab::make(__('Configuration'))
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema(VendorPortalSchema::schema()),
                    Tabs\Tab::make(__('Risk Scoring'))
                        ->icon('heroicon-o-chart-bar')
                        ->schema(RiskScoringSchema::schema()),
                    Tabs\Tab::make(__('Email Templates'))
                        ->icon('heroicon-o-envelope')
                        ->schema(VendorPortalMailSchema::schema()),
                    Tabs\Tab::make(__('Surveys'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema(SurveySettingsSchema::schema()),
                ]),
        ];
    }
}
