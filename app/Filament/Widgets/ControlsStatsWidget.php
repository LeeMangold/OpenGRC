<?php

namespace App\Filament\Widgets;

use App\Enums\Applicability;
use App\Enums\Effectiveness;
use App\Models\Control;
use App\Models\Standard;
use Filament\Widgets\ChartWidget;

class ControlsStatsWidget extends ChartWidget
{
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
        $in_scope_standards = Standard::where('status', 'In Scope')->get();

        $effective = Control::where('effectiveness', Effectiveness::EFFECTIVE)
            ->where('applicability', Applicability::APPLICABLE)
            ->whereIn('standard_id', $in_scope_standards->pluck('id'))
            ->count() ?: 0;
        $partial = Control::where('effectiveness', Effectiveness::PARTIAL)
            ->where('applicability', Applicability::APPLICABLE)
            ->whereIn('standard_id', $in_scope_standards->pluck('id'))
            ->count() ?: 0;
        $ineffective = Control::where('effectiveness', Effectiveness::INEFFECTIVE)
            ->where('applicability', Applicability::APPLICABLE)
            ->whereIn('standard_id', $in_scope_standards->pluck('id'))
            ->count() ?: 0;
        $unknown = Control::where('effectiveness', Effectiveness::UNKNOWN)
            ->where('applicability', '!=', Applicability::NOTAPPLICABLE)
            ->whereIn('standard_id', $in_scope_standards->pluck('id'))
            ->count() ?: 0;

        return [
            'labels' => [
                __('widgets.controls_stats.effective'),
                __('widgets.controls_stats.partially_effective'),
                __('widgets.controls_stats.ineffective'),
                __('widgets.controls_stats.not_assessed')
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
