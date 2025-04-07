<?php

namespace App\Filament\Resources\ImplementationResource\Pages;

use App\Filament\Resources\ImplementationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditImplementation extends EditRecord
{
    protected static string $resource = ImplementationResource::class;

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
        return 'Edit Implementation';
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
