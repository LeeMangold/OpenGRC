<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FccComplianceActivityWidget;
use App\Filament\Widgets\FccComplianceOverviewWidget;
use App\Filament\Widgets\FccLicenseComplianceTableWidget;
use App\Filament\Widgets\FccRuleCategoryRollupWidget;
use App\Filament\Widgets\FccTopNonCompliantRulesWidget;
use App\Filament\Widgets\FccUpcomingDeadlinesWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Compliance at a Glance';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int|string|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            FccComplianceOverviewWidget::class,
            FccLicenseComplianceTableWidget::class,
            FccRuleCategoryRollupWidget::class,
            FccTopNonCompliantRulesWidget::class,
            FccUpcomingDeadlinesWidget::class,
            FccComplianceActivityWidget::class,
        ];
    }
}
