<?php

namespace App\Filament\Resources\RiskResource\Widgets;

use App\Models\Risk;
use Filament\Widgets\Widget;

class ResidualRisk extends Widget
{
    protected static string $view = 'filament.widgets.risk-map';

    public $grid;

    public $title;

    protected static ?int $sort = 2;

    public function mount($title = 'Residual Risk'): void
    {
        $this->grid = InherentRisk::generateGrid(Risk::all(), 'residual');
        $this->title = $title;
    }
}
