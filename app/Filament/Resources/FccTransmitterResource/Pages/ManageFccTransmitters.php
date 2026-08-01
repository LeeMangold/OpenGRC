<?php

namespace App\Filament\Resources\FccTransmitterResource\Pages;

use App\Filament\Resources\FccTransmitterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccTransmitters extends ManageRecords
{
    protected static string $resource = FccTransmitterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
