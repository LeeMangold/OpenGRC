<?php

namespace App\Filament\Resources\AuditResource\Widgets;

use App\Enums\WorkflowStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AuditStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalAudited = \App\Models\Audit::count();
        $totalInProgress = \App\Models\Audit::where('status', WorkflowStatus::INPROGRESS)->count();
        $totalCompleted = \App\Models\Audit::where('status', WorkflowStatus::COMPLETED)->count();

        return [
            Stat::make('Total Audits', $totalAudited),
            Stat::make('In Progress', $totalInProgress),
            Stat::make('Complete', $totalCompleted),
        ];
    }
}
