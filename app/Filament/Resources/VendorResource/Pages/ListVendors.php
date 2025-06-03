<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVendors extends ListRecords
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTableRecordUrlUsing(): ?\Closure
    {
        return fn ($record) => $this->getResource()::getUrl('view', ['record' => $record]);
    }

    protected function getTableActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
} 