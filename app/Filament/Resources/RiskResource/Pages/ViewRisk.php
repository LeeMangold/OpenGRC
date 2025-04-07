<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRisk extends ViewRecord
{
    protected static string $resource = RiskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make('Update Risk')
                ->slideOver()
                ->after(
                    function ($record) {
                        $record->inherent_risk = $record->inherent_likelihood * $record->inherent_impact;
                        $record->residual_risk = $record->residual_likelihood * $record->residual_impact;
                        $record->save();
                    }
                ),
        ];

    }
}
