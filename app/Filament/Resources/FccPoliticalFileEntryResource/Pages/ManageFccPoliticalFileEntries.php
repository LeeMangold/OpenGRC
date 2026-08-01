<?php

namespace App\Filament\Resources\FccPoliticalFileEntryResource\Pages;

use App\Filament\Resources\FccPoliticalFileEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccPoliticalFileEntries extends ManageRecords
{
    protected static string $resource = FccPoliticalFileEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
