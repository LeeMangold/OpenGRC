<?php

namespace App\Filament\Resources\BundleResource\Widgets;

use Filament\Widgets\Widget;

class BundleHeader extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.resources.bundle-resource.widgets.bundle-header';
}
