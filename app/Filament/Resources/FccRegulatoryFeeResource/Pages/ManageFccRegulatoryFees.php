<?php

namespace App\Filament\Resources\FccRegulatoryFeeResource\Pages;

use App\Filament\Resources\FccRegulatoryFeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccRegulatoryFees extends ManageRecords
{
    protected static string $resource = FccRegulatoryFeeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
