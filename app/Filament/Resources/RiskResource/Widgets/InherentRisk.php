<?php

namespace App\Filament\Resources\RiskResource\Widgets;

use App\Models\Risk;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class InherentRisk extends Widget
{
    protected static string $view = 'filament.widgets.risk-map';

    public $grid;

    public $title;

    protected static ?int $sort = 2;

    public function mount($title = 'Inherent Risk'): void
    {
        $this->grid = $this->generateGrid(Risk::all(), 'inherent');
        $this->title = $title;
    }

    public static function generateGrid(Collection $risks, string $type): array
    {
        $grid = array_fill(0, 5, array_fill(0, 5, []));

        foreach ($risks as $risk) {
            if ($type == 'inherent') {
                $likelihoodIndex = $risk->inherent_likelihood - 1;
                $impactIndex = $risk->inherent_impact - 1;
            } else {
                $likelihoodIndex = $risk->residual_likelihood - 1;
                $impactIndex = $risk->residual_impact - 1;
            }

            if (isset($grid[$impactIndex][$likelihoodIndex])) {
                $grid[$impactIndex][$likelihoodIndex][] = $risk;
            }
        }

        return $grid;
    }
}
