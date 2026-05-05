<?php

namespace App\Filament\Resources\FccLicenseResource\Pages;

use App\Filament\Resources\FccLicenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFccLicenses extends ListRecords
{
    protected static string $resource = FccLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
