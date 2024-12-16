<?php

namespace App\Filament\Resources\DataRequestResource\Pages;

use App\Enums\ResponseStatus;
use App\Filament\Resources\DataRequestResource;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Filament\Forms;

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
