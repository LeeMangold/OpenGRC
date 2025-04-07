<?php

namespace App\Filament\Resources\DataRequestResource\Pages;

use App\Filament\Resources\DataRequestResource;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditDataRequest extends EditRecord
{
    protected static string $resource = DataRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function form(Form $form): Form
    {
        return DataRequestResource::getEditForm($form);
    }
}
