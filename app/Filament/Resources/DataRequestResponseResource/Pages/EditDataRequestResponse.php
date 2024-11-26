<?php

namespace App\Filament\Resources\DataRequestResponseResource\Pages;

use App\Enums\ResponseStatus;
use App\Filament\Resources\DataRequestResponseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataRequestResponse extends EditRecord
{
    protected static string $resource = DataRequestResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::handleRecordUpdate($record, $data);
        $record->status = ResponseStatus::RESPONDED;
        $record->save();

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return route('filament.app.pages.to-do');
    }
}
