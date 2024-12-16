<?php

namespace App\Filament\Resources\DataRequestResource\Pages;

use App\Filament\Resources\DataRequestResource;
use App\Models\DataRequest;
use App\Models\DataRequestResponse;
use Filament\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewDataRequest extends ViewRecord
{
    protected static string $resource = DataRequestResource::class;

    protected static ?string $title = 'Data Request Viewer';

    public function form(Form $form): Form
    {
        return DataRequestResource::getEditForm($form);
    }

    /**
     * @property DataRequestResponse $record
     */
    protected function getHeaderActions(): array
    {
        /** @var DataRequest $record */
        $record = $this->record;

        return [
            Action::make('back')
                ->label('Back to Audit Item')
                ->icon('heroicon-m-arrow-left')
                ->url(route('filament.app.resources.audit-items.edit', $record->audit_item_id)),
        ];
    }
}
