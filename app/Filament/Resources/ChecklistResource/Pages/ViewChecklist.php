<?php

namespace App\Filament\Resources\ChecklistResource\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Resources\ChecklistResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewChecklist extends ViewRecord
{
    protected static string $resource = ChecklistResource::class;

    protected string $view = 'filament.pages.view-checklist';

    /**
     * Resolve the record with all necessary eager loading in a single query.
     */
    protected function resolveRecord(int|string $key): Model
    {
        return Survey::with([
            'template' => fn ($q) => $q->withCount('questions'),
            'template.questions',
            'answers.attachments',
            'assignedTo',
            'createdBy',
            'latestApproval',
            'approver',
        ])
            ->withCount(['answers as answered_questions_count' => fn ($q) => $q->whereNotNull('answer_value')])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('complete_checklist')
                ->label(__('checklist.checklist.actions.complete'))
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn () => ChecklistResource::getUrl('respond', ['record' => $this->record]))
                ->visible(fn () => in_array($this->record->status, [SurveyStatus::DRAFT, SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])),
            Action::make('approve_checklist')
                ->label(__('checklist.checklist.actions.approve'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->url(fn () => ChecklistResource::getUrl('approve', ['record' => $this->record]))
                ->visible(fn () => $this->record->status === SurveyStatus::COMPLETED
                    && ! $this->record->isApproved()
                    && $this->record->canBeApprovedBy(auth()->user())),
        ];
    }
}
