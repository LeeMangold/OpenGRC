<?php

namespace App\Filament\Resources\FccEasTestResource\Pages;

use App\Filament\Resources\FccEasTestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccEasTests extends ManageRecords
{
    protected static string $resource = FccEasTestResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
