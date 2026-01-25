<?php

namespace App\Filament\Widgets;

use App\Enums\Applicability;
use App\Enums\WorkflowStatus;
use App\Models\Audit;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Program;
use App\Models\Standard;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    public ?Program $program = null;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        if ($this->program) {
            return $this->getProgramScopedStats();
        }

        return $this->getGlobalStats();
    }

    protected function getGlobalStats(): array
    {
        // Single query for audit counts using conditional aggregation
        $auditCounts = Audit::selectRaw('
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed
        ', [WorkflowStatus::INPROGRESS->value, WorkflowStatus::COMPLETED->value])
            ->first();

        $audits_in_progress = (int) ($auditCounts->in_progress ?? 0);
        $audits_performed = (int) ($auditCounts->completed ?? 0);
        $implementations = Implementation::count();

        // Get in-scope standard IDs once
        $inScopeStandardIds = Standard::where('status', 'In Scope')->pluck('id');

        // Single query for controls in scope (not N/A)
        $controls_in_scope_count = Control::whereIn('standard_id', $inScopeStandardIds)
            ->where('applicability', '!=', Applicability::NOTAPPLICABLE)
            ->count();

        return [
            Stat::make(__('widgets.stats.audits_in_progress'), $audits_in_progress),
            Stat::make(__('widgets.stats.audits_completed'), $audits_performed),
            Stat::make(__('widgets.stats.controls_in_scope'), $controls_in_scope_count),
            Stat::make(__('widgets.stats.implementations'), $implementations),
        ];
    }

    protected function getProgramScopedStats(): array
    {
        // Single query for audit counts using conditional aggregation
        $auditCounts = Audit::where('program_id', $this->program->id)
            ->selectRaw('
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed
            ', [WorkflowStatus::INPROGRESS->value, WorkflowStatus::COMPLETED->value])
            ->first();

        $audits_in_progress = (int) ($auditCounts->in_progress ?? 0);
        $audits_performed = (int) ($auditCounts->completed ?? 0);

        // Get all controls for this program (from standards and direct)
        $allControls = $this->program->getAllControls();
        $controlIds = $allControls->pluck('id')->toArray();

        // Get implementations scoped to this program's controls
        $implementations = Implementation::whereHas('controls', function ($query) use ($controlIds) {
            $query->whereIn('controls.id', $controlIds);
        })->count();

        // Get in-scope standard IDs for this program
        $programStandardIds = $this->program->standards()->where('status', 'In Scope')->pluck('standards.id');

        // Single query for controls in scope (not N/A)
        $controls_in_scope_count = Control::whereIn('standard_id', $programStandardIds)
            ->where('applicability', '!=', Applicability::NOTAPPLICABLE)
            ->count();

        return [
            Stat::make(__('widgets.stats.audits_in_progress'), $audits_in_progress),
            Stat::make(__('widgets.stats.audits_completed'), $audits_performed),
            Stat::make(__('widgets.stats.controls_in_scope'), $controls_in_scope_count),
            Stat::make(__('widgets.stats.implementations'), $implementations),
        ];
    }
}
