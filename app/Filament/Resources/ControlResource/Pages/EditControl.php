<?php

namespace App\Filament\Resources\ControlResource\Pages;

use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Resources\ControlResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditControl extends EditRecord
{
    use HasTaxonomyFields;

    protected static string $resource = ControlResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Control';
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        parent::save($shouldRedirect);

        if ($shouldRedirect) {
            $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]));
        }
    }
}
