<?php

namespace App\Filament\Pages;

use App\Filament\Resources\VendorResource;
use App\Filament\Widgets\SurveyInfoWidget;
use App\Filament\Widgets\SurveysTableWidget;
use App\Filament\Widgets\SurveyTemplatesTableWidget;
use App\Filament\Widgets\VendorsTableWidget;
use App\Filament\Widgets\VendorStatsWidget;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class VendorManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.vendor-manager';

    #[Url]
    public string $activeTab = 'vendors';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        return $user->can('List Vendors') || $user->can('List Surveys');
    }

    public static function getNavigationLabel(): string
    {
        return __('Vendor Management');
    }

    public function getTitle(): string
    {
        return __('Vendor Management');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addVendor')
                ->label(__('Add Vendor'))
                ->icon('heroicon-o-plus')
                ->url(VendorResource::getUrl('create'))
                ->visible(fn () => auth()->check() && auth()->user()->can('Create Vendors')),
            Action::make('settings')
                ->label(__('Settings'))
                ->icon('heroicon-o-ellipsis-vertical')
                ->url('/admin/vendor-portal-settings')
                ->color('gray')
                ->visible(fn () => auth()->user()?->can('Manage Vendor Management')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        // Show contextual widgets based on active tab
        return match ($this->activeTab) {
            'surveys', 'templates' => [SurveyInfoWidget::class],
            default => [VendorStatsWidget::class],
        };
    }

    protected function getFooterWidgets(): array
    {
        return match ($this->activeTab) {
            'surveys' => [SurveysTableWidget::class],
            'templates' => [SurveyTemplatesTableWidget::class],
            default => [VendorsTableWidget::class],
        };
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return 1;
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
            'tabs' => [
                'vendors' => [
                    'label' => __('Vendors'),
                    'icon' => 'heroicon-o-building-storefront',
                ],
                'surveys' => [
                    'label' => __('Vendor Surveys'),
                    'icon' => 'heroicon-o-paper-airplane',
                ],
                'templates' => [
                    'label' => __('Survey Templates'),
                    'icon' => 'heroicon-o-clipboard-document-list',
                ],
            ],
        ];
    }
}
