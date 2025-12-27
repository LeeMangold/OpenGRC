<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TrustCenter\CertificationsWidget;
use App\Filament\Widgets\TrustCenter\ContentBlocksWidget;
use App\Filament\Widgets\TrustCenter\PendingAccessRequestsWidget;
use App\Filament\Widgets\TrustCenter\TrustCenterDocumentsWidget;
use App\Filament\Widgets\TrustCenter\TrustCenterStatsWidget;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class TrustCenterManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 15;

    protected static string $view = 'filament.pages.trust-center-manager';

    #[Url]
    public string $activeTab = 'documents';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        return $user->can('Manage Trust Center') || $user->can('Manage Trust Access');
    }

    public static function getNavigationLabel(): string
    {
        return __('Trust Center');
    }

    public static function getNavigationGroup(): ?string
    {
        return null; // Top-level navigation
    }

    public function getTitle(): string
    {
        return __('Trust Center');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewPublicTrustCenter')
                ->label(__('View Public Page'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(route('trust-center.index'))
                ->openUrlInNewTab()
                ->color('gray'),
            Action::make('settings')
                ->label(__('Settings'))
                ->icon('heroicon-o-ellipsis-vertical')
                ->url('/admin/trust-center-settings')
                ->color('gray')
                ->visible(fn () => auth()->user()?->can('Manage Trust Center')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TrustCenterStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return match ($this->activeTab) {
            'documents' => [TrustCenterDocumentsWidget::class],
            'certifications' => [CertificationsWidget::class],
            'access_requests' => [PendingAccessRequestsWidget::class],
            'content' => [ContentBlocksWidget::class],
            default => [TrustCenterDocumentsWidget::class],
        };
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
                'documents' => [
                    'label' => __('Documents'),
                    'icon' => 'heroicon-o-document-text',
                ],
                'certifications' => [
                    'label' => __('Certifications'),
                    'icon' => 'heroicon-o-shield-check',
                ],
                'access_requests' => [
                    'label' => __('Access Requests'),
                    'icon' => 'heroicon-o-inbox',
                ],
                'content' => [
                    'label' => __('Content Blocks'),
                    'icon' => 'heroicon-o-squares-2x2',
                ],
            ],
        ];
    }
}
