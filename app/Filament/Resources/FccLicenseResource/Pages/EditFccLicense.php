<?php

namespace App\Filament\Resources\FccLicenseResource\Pages;

use App\Filament\Resources\FccLicenseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFccLicense extends EditRecord
{
    protected static string $resource = FccLicenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
