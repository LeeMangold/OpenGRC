<?php

namespace App\Filament\Widgets;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Models\Control;
use App\Models\Standard;
use Filament\Widgets\ChartWidget;

class ControlsStatsWidget extends ChartWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = null;

    protected static ?string $maxHeight = '250px';

    protected int|string|array $columnSpan = '1';

    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return __('widgets.controls_stats.heading');
    }

    protected function getData(): array
    {
        $inScopeStandardIds = Standard::where('status', 'In Scope')->pluck('id');

        // Single query with conditional aggregation for all effectiveness counts
        $counts = Control::whereIn('standard_id', $inScopeStandardIds)
            ->selectRaw('
                SUM(CASE WHEN effectiveness = ? AND applicability = ? THEN 1 ELSE 0 END) as effective,
                SUM(CASE WHEN effectiveness = ? AND applicability = ? THEN 1 ELSE 0 END) as partial,
                SUM(CASE WHEN effectiveness = ? AND applicability = ? THEN 1 ELSE 0 END) as ineffective,
                SUM(CASE WHEN effectiveness = ? AND applicability != ? THEN 1 ELSE 0 END) as unknown
            ', [
                Effectiveness::EFFECTIVE->value, Applicability::APPLICABLE->value,
                Effectiveness::PARTIAL->value, Applicability::APPLICABLE->value,
                Effectiveness::INEFFECTIVE->value, Applicability::APPLICABLE->value,
                Effectiveness::UNKNOWN->value, Applicability::NOTAPPLICABLE->value,
            ])
            ->first();

        $effective = (int) ($counts->effective ?? 0);
        $partial = (int) ($counts->partial ?? 0);
        $ineffective = (int) ($counts->ineffective ?? 0);
        $unknown = (int) ($counts->unknown ?? 0);

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
