<?php

namespace App\Filament\Widgets;

use App\Enums\Effectiveness;
use App\Models\Implementation;
use Filament\Widgets\ChartWidget;

class ImplementationsStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'Implementation Effectiveness';

    protected static ?string $maxHeight = '250px';

    protected int|string|array $columnSpan = '1';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $effective = Implementation::where('effectiveness', Effectiveness::EFFECTIVE)->count();
        $partial = Implementation::where('effectiveness', Effectiveness::PARTIAL)->count();
        $ineffective = Implementation::where('effectiveness', Effectiveness::INEFFECTIVE)->count();
        $unknown = Implementation::where('effectiveness', Effectiveness::UNKNOWN)->count();

        return [
            'labels' => ['Effective', 'Partially Effective', 'Ineffective', 'Not Assessed'],
            'datasets' => [
                [
                    'data' => [$effective, $partial, $ineffective, $unknown],
                    'backgroundColor' => [
                        'rgb(45, 180, 45)',
                        'rgb(220, 180, 35)',
                        'rgb(255, 99, 132)',
                        'rgb(90, 90, 90)',
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
