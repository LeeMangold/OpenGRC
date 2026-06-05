<?php

namespace App\Filament\Resources\FccStationLogEntryResource\Pages;

use App\Filament\Resources\FccStationLogEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccStationLogEntries extends ManageRecords
{
    protected static string $resource = FccStationLogEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
