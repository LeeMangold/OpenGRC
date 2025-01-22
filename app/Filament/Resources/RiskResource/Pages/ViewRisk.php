<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\Rules\In;

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
