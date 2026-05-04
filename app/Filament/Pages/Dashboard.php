<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FccComplianceActivityWidget;
use App\Filament\Widgets\FccComplianceOverviewWidget;
use App\Filament\Widgets\FccLicenseComplianceTableWidget;
use App\Filament\Widgets\FccRuleCategoryRollupWidget;
use App\Filament\Widgets\FccTopNonCompliantRulesWidget;
use App\Filament\Widgets\FccUpcomingDeadlinesWidget;

class Dashboard extends TabbedPage
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Compliance at a Glance';

    protected static ?int $navigationSort = -2;

    public function getStatsWidgets(): array
    {
        return [
            FccComplianceOverviewWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            FccLicenseComplianceTableWidget::class,
            FccRuleCategoryRollupWidget::class,
            FccTopNonCompliantRulesWidget::class,
            FccUpcomingDeadlinesWidget::class,
            FccComplianceActivityWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}
