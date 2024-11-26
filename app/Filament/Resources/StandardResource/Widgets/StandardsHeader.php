<?php

namespace App\Filament\Resources\StandardResource\Widgets;

use Filament\Widgets\Widget;

class StandardsHeader extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.resources.standard-resource.widgets.standards-header';
}
