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

    public function mount($title = 'Inherent Risk')
    {
        $this->grid = $this->generateGrid(Risk::all(), 'inherent');
        $this->title = $title;
    }

    public static function generateGrid(Collection $risks, string $type): array
    {
        $grid = array_fill(0, 5, array_fill(0, 5, 0));

        foreach ($risks as $risk) {
            if ($type == 'inherent') {
                $likelihoodIndex = $risk->inherent_likelihood - 1;
                $impactIndex = $risk->inherent_impact - 1;
            } else {
                $likelihoodIndex = $risk->residual_likelihood - 1;
                $impactIndex = $risk->residual_impact - 1;
            }

            if (isset($grid[$impactIndex][$likelihoodIndex])) {
                $grid[$impactIndex][$likelihoodIndex]++;
            }
        }

        return $grid;
    }

    public static function getRiskColor(int $likelihood, int $impact, int $weight = 200): string
    {
        $average = ($likelihood + $impact) / 2;

        if ($average >= 4) {
            return "bg-red-$weight"; // High risk
        } elseif ($average >= 3) {
            return "bg-orange-$weight"; // Moderate-High risk
        } elseif ($average >= 2) {
            return "bg-yellow-$weight"; // Moderate risk
        } else {
            return "bg-green-$weight"; // Low risk
        }
    }
}
