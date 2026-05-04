<?php

namespace App\Filament\Resources\FccIssuesProgramsListResource\Pages;

use App\Filament\Resources\FccIssuesProgramsListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccIssuesProgramsLists extends ManageRecords
{
    protected static string $resource = FccIssuesProgramsListResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
