<?php

namespace App\Filament\Resources\ImplementationResource\Pages;

use App\Filament\Resources\ImplementationResource;
use App\Filament\Resources\ImplementationResource\Widgets\ImplementationsHeader;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListImplementations extends ListRecords
{
    protected static string $resource = ImplementationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Create Implementation'),
        ];
    }
}
