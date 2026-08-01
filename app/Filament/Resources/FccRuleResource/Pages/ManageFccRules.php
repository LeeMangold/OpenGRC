<?php

namespace App\Filament\Resources\FccRuleResource\Pages;

use App\Filament\Resources\FccRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFccRules extends ManageRecords
{
    protected static string $resource = FccRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
