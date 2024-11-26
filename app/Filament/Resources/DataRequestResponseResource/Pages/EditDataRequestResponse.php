<?php

namespace App\Filament\Resources\DataRequestResponseResource\Pages;

use App\Enums\ResponseStatus;
use App\Filament\Resources\DataRequestResponseResource;
use App\Models\DataRequestResponse;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditDataRequestResponse extends EditRecord
{
    protected static string $resource = DataRequestResponseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var DataRequestResponse $record */
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
