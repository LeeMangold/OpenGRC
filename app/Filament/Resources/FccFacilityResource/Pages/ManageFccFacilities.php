<?php

namespace App\Filament\Resources\FccFacilityResource\Pages;

use App\Filament\Resources\FccFacilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccFacilities extends ManageRecords
{
    protected static string $resource = FccFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
