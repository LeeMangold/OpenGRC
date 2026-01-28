<?php

namespace App\Filament\Resources\ChecklistResource\Pages;

use App\Enums\SurveyStatus;
use App\Filament\Resources\ChecklistResource;
use App\Models\Survey;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ApproveChecklist extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected static string $resource = ChecklistResource::class;

    protected string $view = 'filament.pages.approve-checklist';

    public Survey|Model|int|string|null $record = null;

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = Survey::with(['template.questions', 'answers', 'assignedTo', 'createdBy'])->findOrFail($record);

        // Check if user has permission
        if (! auth()->user()->can('Update Checklists')) {
            abort(403);
        }

        // Check if user is authorized to approve this checklist
        if (! $this->record->canBeApprovedBy(auth()->user())) {
            Notification::make()
                ->title(__('checklist.checklist.notifications.not_authorized_approver'))
                ->body(__('checklist.checklist.notifications.not_authorized_approver_body'))
                ->danger()
                ->send();

            $this->redirect(ChecklistResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        // Check if checklist can be approved
        if ($this->record->status !== SurveyStatus::COMPLETED) {
            Notification::make()
                ->title(__('checklist.checklist.notifications.cannot_approve'))
                ->body(__('checklist.checklist.notifications.cannot_approve_incomplete'))
                ->warning()
                ->send();

            $this->redirect(ChecklistResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        if ($this->record->isApproved()) {
            Notification::make()
                ->title(__('checklist.checklist.notifications.already_approved'))
                ->body(__('checklist.checklist.notifications.already_approved_body'))
                ->info()
                ->send();

            $this->redirect(ChecklistResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                Section::make(__('checklist.checklist.pages.approve.signature_section'))
                    ->description(__('checklist.checklist.pages.approve.signature_description'))
                    ->schema([
                        TextInput::make('approval_signature')
                            ->label(__('checklist.checklist.form.approval_signature.label'))
                            ->helperText(__('checklist.checklist.form.approval_signature.helper'))
                            ->required()
                            ->placeholder(__('checklist.checklist.form.approval_signature.placeholder')),
                        Textarea::make('approval_notes')
                            ->label(__('checklist.checklist.form.approval_notes.label'))
                            ->helperText(__('checklist.checklist.form.approval_notes.helper'))
                            ->rows(3)
                            ->maxLength(2000),
                    ]),
            ])
            ->statePath('data');
    }

    public function checklistSummaryInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->components([
                Section::make(__('checklist.checklist.pages.approve.summary_section'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('display_title')
                            ->label(__('checklist.checklist.form.title.label')),
                        TextEntry::make('template.title')
                            ->label(__('checklist.checklist.form.template.label')),
                        TextEntry::make('status')
                            ->label(__('checklist.checklist.form.status.label'))
                            ->badge(),
                        TextEntry::make('assignedTo.name')
                            ->label(__('checklist.checklist.form.assigned_to.label'))
                            ->default('-'),
                        TextEntry::make('completed_at')
                            ->label(__('checklist.checklist.table.columns.completed_at'))
                            ->dateTime(),
                        TextEntry::make('progress')
                            ->label(__('checklist.checklist.table.columns.progress'))
                            ->suffix('%'),
                    ]),
            ]);
    }

    public function approve(): void
    {
        $data = $this->form->getState();

        if (empty($data['approval_signature'])) {
            Notification::make()
                ->title(__('checklist.checklist.notifications.signature_required'))
                ->body(__('checklist.checklist.notifications.signature_required_body'))
                ->danger()
                ->send();

            return;
        }

        // Use the Approvable trait's approve method
        $this->record->approve(
            auth()->user(),
            $data['approval_signature'],
            $data['approval_notes'] ?? null
        );

        Notification::make()
            ->title(__('checklist.checklist.notifications.approved'))
            ->body(__('checklist.checklist.notifications.approved_body'))
            ->success()
            ->send();

        $this->redirect(ChecklistResource::getUrl('view', ['record' => $this->record]));
    }

    public function getTitle(): string
    {
        return __('checklist.checklist.pages.approve.title');
    }

    public function getSubheading(): ?string
    {
        return $this->record->display_title;
    }

    public function getBreadcrumbs(): array
    {
        return [
            ChecklistResource::getUrl() => __('checklist.checklist.navigation.label'),
            ChecklistResource::getUrl('view', ['record' => $this->record]) => $this->record?->display_title ?? __('Checklist'),
            __('checklist.checklist.pages.approve.breadcrumb'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('Back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(ChecklistResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('checklist.checklist.actions.approve'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action('approve')
                ->requiresConfirmation()
                ->modalHeading(__('checklist.checklist.modals.approve.heading'))
                ->modalDescription(__('checklist.checklist.modals.approve.description'))
                ->modalSubmitActionLabel(__('checklist.checklist.modals.approve.confirm')),
        ];
    }
}
