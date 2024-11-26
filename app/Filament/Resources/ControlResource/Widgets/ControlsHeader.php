<?php

namespace App\Filament\Resources\ControlResource\Widgets;

use Filament\Widgets\Widget;

class ControlsHeader extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.resources.control-resource.widgets.controls-header';
}
