<?php

namespace App\Filament\Resources\ChecklistTemplateResource\Pages;

use App\Enums\SurveyTemplateStatus;
use App\Filament\Resources\ChecklistResource;
use App\Filament\Resources\ChecklistTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChecklistTemplate extends ViewRecord
{
    protected static string $resource = ChecklistTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('create_checklist')
                ->label(__('checklist.template.actions.create_checklist'))
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->url(fn () => ChecklistResource::getUrl('create', ['template' => $this->record->id]))
                ->visible(fn () => $this->record->status === SurveyTemplateStatus::ACTIVE),
        ];
    }
}
