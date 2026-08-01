<?php

namespace App\Filament\Resources\FccAsrRegistrationResource\Pages;

use App\Filament\Resources\FccAsrRegistrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccAsrRegistrations extends ManageRecords
{
    protected static string $resource = FccAsrRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
