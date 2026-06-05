<?php

namespace App\Filament\Resources\FccPublicFileDocumentResource\Pages;

use App\Filament\Resources\FccPublicFileDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccPublicFileDocuments extends ManageRecords
{
    protected static string $resource = FccPublicFileDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
