<?php

namespace App\Filament\Widgets;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ControlsStatsWidget extends ChartWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = null;

    protected ?string $maxHeight = '250px';

    protected int|string|array $columnSpan = '1';

    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return __('widgets.controls_stats.heading');
    }

    protected function getData(): array
    {
        // Single query with conditional aggregation and inline subquery for in-scope standards
        $counts = DB::selectOne('
            SELECT
                COALESCE(SUM(CASE WHEN effectiveness = ? AND applicability = ? THEN 1 ELSE 0 END), 0) as effective,
                COALESCE(SUM(CASE WHEN effectiveness = ? AND applicability = ? THEN 1 ELSE 0 END), 0) as partial,
                COALESCE(SUM(CASE WHEN effectiveness = ? AND applicability = ? THEN 1 ELSE 0 END), 0) as ineffective,
                COALESCE(SUM(CASE WHEN effectiveness = ? AND applicability != ? THEN 1 ELSE 0 END), 0) as unknown
            FROM controls
            WHERE standard_id IN (SELECT id FROM standards WHERE status = ?)
        ', [
            Effectiveness::EFFECTIVE->value, Applicability::APPLICABLE->value,
            Effectiveness::PARTIAL->value, Applicability::APPLICABLE->value,
            Effectiveness::INEFFECTIVE->value, Applicability::APPLICABLE->value,
            Effectiveness::UNKNOWN->value, Applicability::NOTAPPLICABLE->value,
            'In Scope',
        ]);

        $effective = (int) $counts->effective;
        $partial = (int) $counts->partial;
        $ineffective = (int) $counts->ineffective;
        $unknown = (int) $counts->unknown;

        return [
            'labels' => [
                __('widgets.controls_stats.effective'),
                __('widgets.controls_stats.partially_effective'),
                __('widgets.controls_stats.ineffective'),
                __('widgets.controls_stats.not_assessed'),
            ],
            'datasets' => [
                [
                    'data' => [$effective, $partial, $ineffective, $unknown],
                    'backgroundColor' => [
                        'rgb(52, 211, 153)',
                        'rgb(252, 211, 77)',
                        'rgb(244, 114, 182)',
                        'rgb(107, 114, 128)',
                    ],
                    'borderWidth' => [0, 0, 0, 0],
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
