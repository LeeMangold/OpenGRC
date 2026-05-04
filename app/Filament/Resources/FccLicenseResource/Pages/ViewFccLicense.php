<?php

namespace App\Filament\Resources\FccLicenseResource\Pages;

use App\Filament\Resources\FccLicenseResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFccLicense extends ViewRecord
{
    protected static string $resource = FccLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
