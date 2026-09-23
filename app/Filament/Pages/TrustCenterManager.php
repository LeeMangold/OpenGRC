<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\TrustCenter\CertificationsWidget;
use App\Filament\Widgets\TrustCenter\ContentBlocksWidget;
use App\Filament\Widgets\TrustCenter\PendingAccessRequestsWidget;
use App\Filament\Widgets\TrustCenter\TrustCenterDocumentsWidget;
use App\Filament\Widgets\TrustCenter\TrustCenterStatsWidget;
use Filament\Actions\Action;

class TrustCenterManager extends TabbedPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Trust Center';

    protected static ?string $title = 'Trust Center';

    protected static ?int $navigationSort = 15;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'can')) {
            return false;
        }

        return $user->can('Manage Trust Center') || $user->can('Manage Trust Access');
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

    public function getTabs(): array
    {
        $tabs = [
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
        ];

        // "Manage Trust Access" alone only grants access request review.
        if (! auth()->user()?->can('Manage Trust Center')) {
            return array_intersect_key($tabs, ['access_requests' => true]);
        }

        return $tabs;
    }

    public function getWidgets(): array
    {
        $tab = array_key_exists($this->activeTab, $this->getTabs())
            ? $this->activeTab
            : array_key_first($this->getTabs());

        return match ($tab) {
            'documents' => [TrustCenterDocumentsWidget::class],
            'certifications' => [CertificationsWidget::class],
            'access_requests' => [PendingAccessRequestsWidget::class],
            'content' => [ContentBlocksWidget::class],
            default => [],
        };
    }

    public function getStatsWidgets(): array
    {
        return [TrustCenterStatsWidget::class];
    }
}
