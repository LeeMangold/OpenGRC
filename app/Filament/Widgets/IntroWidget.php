<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class IntroWidget extends Widget
{
    protected static string $view = 'filament.widgets.intro-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = '2';

    protected static ?string $title = 'Welcome to the Dashboard';
}
