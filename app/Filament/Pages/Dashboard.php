<?php

namespace App\Filament\Pages;

use JibayMcs\FilamentTour\Tour\HasTour;
use JibayMcs\FilamentTour\Tour\Step;
use JibayMcs\FilamentTour\Tour\Tour;

class Dashboard extends \Filament\Pages\Dashboard
{
    use HasTour;

    public function getColumns(): int|string|array
    {
        return 3;
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverview::class,
            \App\Filament\Widgets\ControlsStatsWidget::class,
            //            \App\Filament\Widgets\IntroWidget::class,
            \App\Filament\Widgets\AuditListWidget::class,
            \App\Filament\Widgets\ImplementationsStatsWidget::class,
            \App\Filament\Widgets\ToDoListWidget::class,

        ];
    }

    /**
     * @throws \Exception
     */
    // todo: add the tour to the dashboard
    public function tours(): array
    {
        return [
            Tour::make('dashboard')
                ->colors('primary', 'light')
                ->steps(
                    Step::make()
                        ->title('Welcome to OpenGRC !')
                        ->description(view('tutorial.dashboard.introduction')),

                    Step::make('.fi-avatar')
                        ->title('Woaw ! Here is your avatar !')
                        ->description('You look nice !')
                        ->icon('heroicon-o-user-circle')
                        ->iconColor('primary')
                ),
        ];
    }
}
