<?php

namespace App\Filament\Resources\FccFormFilingResource\Pages;

use App\Filament\Resources\FccFormFilingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccFormFilings extends ManageRecords
{
    protected static string $resource = FccFormFilingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
