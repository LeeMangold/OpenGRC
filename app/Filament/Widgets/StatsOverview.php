<?php

namespace App\Filament\Widgets;

use App\Enums\Applicability;
use App\Enums\WorkflowStatus;
use App\Models\Program;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

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
        // Single query for all stats using scalar subqueries
        $stats = DB::selectOne('
            SELECT
                (SELECT COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) FROM audits) as audits_in_progress,
                (SELECT COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) FROM audits) as audits_completed,
                (SELECT COUNT(*) FROM implementations) as implementations,
                (SELECT COUNT(*) FROM controls WHERE standard_id IN (SELECT id FROM standards WHERE status = ?) AND applicability != ?) as controls_in_scope
        ', [
            WorkflowStatus::INPROGRESS->value,
            WorkflowStatus::COMPLETED->value,
            'In Scope',
            Applicability::NOTAPPLICABLE->value,
        ]);

        return [
            Stat::make(__('widgets.stats.audits_in_progress'), (int) $stats->audits_in_progress),
            Stat::make(__('widgets.stats.audits_completed'), (int) $stats->audits_completed),
            Stat::make(__('widgets.stats.controls_in_scope'), (int) $stats->controls_in_scope),
            Stat::make(__('widgets.stats.implementations'), (int) $stats->implementations),
        ];
    }

    protected function getProgramScopedStats(): array
    {
        $programId = $this->program->id;

        // Single query for all program-scoped stats using scalar subqueries
        $stats = DB::selectOne('
            SELECT
                (SELECT COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) FROM audits WHERE program_id = ?) as audits_in_progress,
                (SELECT COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) FROM audits WHERE program_id = ?) as audits_completed,
                (SELECT COUNT(*) FROM implementations WHERE EXISTS (
                    SELECT 1 FROM control_implementation ci
                    WHERE ci.implementation_id = implementations.id
                    AND (
                        ci.control_id IN (SELECT id FROM controls WHERE standard_id IN (SELECT standard_id FROM program_standard WHERE program_id = ?))
                        OR ci.control_id IN (SELECT control_id FROM control_program WHERE program_id = ?)
                    )
                )) as implementations,
                (SELECT COUNT(*) FROM controls
                    WHERE applicability != ?
                    AND standard_id IN (
                        SELECT ps.standard_id FROM program_standard ps
                        JOIN standards s ON s.id = ps.standard_id
                        WHERE ps.program_id = ? AND s.status = ?
                    )
                ) as controls_in_scope
        ', [
            WorkflowStatus::INPROGRESS->value,
            $programId,
            WorkflowStatus::COMPLETED->value,
            $programId,
            $programId,
            $programId,
            Applicability::NOTAPPLICABLE->value,
            $programId,
            'In Scope',
        ]);

        return [
            Stat::make(__('widgets.stats.audits_in_progress'), (int) $stats->audits_in_progress),
            Stat::make(__('widgets.stats.audits_completed'), (int) $stats->audits_completed),
            Stat::make(__('widgets.stats.controls_in_scope'), (int) $stats->controls_in_scope),
            Stat::make(__('widgets.stats.implementations'), (int) $stats->implementations),
        ];
    }
}
