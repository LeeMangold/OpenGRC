<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getColumns(): int|string|array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\IntroWidget::class,
            //            \App\Filament\Widgets\ImplementationsStatsWidget::class,
            \App\Filament\Widgets\ControlsStatsWidget::class,

        ];
    }
}
