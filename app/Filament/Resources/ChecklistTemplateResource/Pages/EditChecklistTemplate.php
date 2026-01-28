<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Filament\Resources\ChecklistTemplateResource;
use App\Models\SurveyTemplate;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditChecklistTemplate extends EditRecord
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->hidden(fn (SurveyTemplate $record): bool => $record->hasChecklists()),
            ForceDeleteAction::make()
                ->hidden(fn (SurveyTemplate $record): bool => $record->hasChecklists()),
            RestoreAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    protected function beforeFill(): void
    {
        if ($this->record->isLocked()) {
            Notification::make()
                ->title(__('checklist.template.notifications.locked_title'))
                ->body(__('checklist.template.notifications.locked_body'))
                ->warning()
                ->persistent()
                ->send();
        }
    }
}
