<?php

namespace App\Filament\Resources\TrustCenterContentBlockResource\Pages;

use App\Filament\Resources\TrustCenterContentBlockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrustCenterContentBlock extends EditRecord
{
    protected static string $resource = TrustCenterContentBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
