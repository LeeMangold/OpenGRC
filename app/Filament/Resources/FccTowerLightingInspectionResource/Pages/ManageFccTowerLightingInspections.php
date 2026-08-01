<?php

namespace App\Filament\Resources\FccTowerLightingInspectionResource\Pages;

use App\Filament\Resources\FccTowerLightingInspectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccTowerLightingInspections extends ManageRecords
{
    protected static string $resource = FccTowerLightingInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
