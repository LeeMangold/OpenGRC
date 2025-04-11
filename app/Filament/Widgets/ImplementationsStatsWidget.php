<?php

namespace App\Filament\Widgets;

use App\Enums\Effectiveness;
use App\Models\Implementation;
use Filament\Widgets\ChartWidget;

class ImplementationsStatsWidget extends ChartWidget
{
    protected static ?string $heading = null;

    protected static ?string $maxHeight = '250px';

    protected int|string|array $columnSpan = '1';

    protected static ?int $sort = 2;

    public function getHeading(): ?string
    {
        return __('widgets.implementations_stats.heading');
    }

    protected function getData(): array
    {
        $effective = Implementation::where('effectiveness', Effectiveness::EFFECTIVE)->count();
        $partial = Implementation::where('effectiveness', Effectiveness::PARTIAL)->count();
        $ineffective = Implementation::where('effectiveness', Effectiveness::INEFFECTIVE)->count();
        $unknown = Implementation::where('effectiveness', Effectiveness::UNKNOWN)->count();

        return [
            'labels' => [
                __('widgets.implementations_stats.effective'),
                __('widgets.implementations_stats.partially_effective'),
                __('widgets.implementations_stats.ineffective'),
                __('widgets.implementations_stats.not_assessed')
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
