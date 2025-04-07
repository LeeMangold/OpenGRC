<?php

namespace App\Filament\Resources\ImplementationResource\Pages;

use App\Filament\Resources\ImplementationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewImplementations extends ViewRecord
{
    protected static string $resource = ImplementationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make('Update Implementation')
                ->slideOver(),
        ];
    }
}
