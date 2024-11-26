<?php

namespace App\Filament\Resources\AuditResource\Widgets;

use Filament\Widgets\Widget;

class TextWidget extends Widget
{
    //    protected static ?int $sort = 1;
    //    protected int | string | array $columnSpan = '2';
    //    protected static ?string $title = '';
    protected static string $view = 'filament.widgets.text-widget';

    public ?string $message = null;

    public ?string $bg_color = 'white';

    public ?string $fg_color = 'black';

    public ?string $icon = 'heroicon-m-information-circle';
}
