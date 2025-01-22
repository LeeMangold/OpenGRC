<?php

namespace App\Filament\Resources\RiskResource\Pages;

use App\Filament\Resources\RiskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRisk extends CreateRecord
{
    protected static string $resource = RiskResource::class;

    protected function afterSave()
    {
        $inherant_risk = $this->record->inherent_likelihood * $this->record->inherent_impact;
        $residual_risk = $this->record->residual_likelihood * $this->record->residual_impact;
        $this->record->inherent_risk = $inherant_risk;
        $this->record->residual_risk = $residual_risk;
        $this->record->save();

        return redirect()->route('filament.app.resources.risks.index');
    }
}
