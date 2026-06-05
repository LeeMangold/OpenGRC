<?php

namespace App\Filament\Resources\FccDeadlineResource\Pages;

use App\Filament\Resources\FccDeadlineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccDeadlines extends ManageRecords
{
    protected static string $resource = FccDeadlineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
