<?php

namespace App\Filament\Resources\ImplementationResource\Widgets;

use Filament\Widgets\Widget;

class ImplementationsHeader extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.resources.implementation-resource.widgets.implementations-header';
}
