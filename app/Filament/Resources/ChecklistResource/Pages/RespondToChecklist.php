<?php

namespace App\Filament\Resources\ChecklistResource\Pages;

use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Filament\Resources\ChecklistResource;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAttachment;
use App\Models\SurveyQuestion;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class RespondToChecklist extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ChecklistResource::class;

    protected string $view = 'filament.pages.respond-to-checklist';

    public Survey|Model|int|string|null $record = null;

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = Survey::findOrFail($record);

        // Check if user has permission
        if (! auth()->user()->can('Update Checklists')) {
            abort(403);
        }

        // Check if checklist can be responded to
        if (! in_array($this->record->status, [SurveyStatus::DRAFT, SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])) {
            Notification::make()
                ->title(__('checklist.checklist.notifications.cannot_modify'))
                ->body(__('checklist.checklist.notifications.cannot_modify_body'))
                ->warning()
                ->send();

            $this->redirect(ChecklistResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        // Load existing answers
        $this->loadExistingAnswers();
    }

    protected function loadExistingAnswers(): void
    {
        $data = [];

        foreach ($this->record->template->questions as $question) {
            $answer = $this->record->answers()
                ->where('survey_question_id', $question->id)
                ->with('attachments')
                ->first();

            if ($answer) {
                if ($question->question_type === QuestionType::FILE) {
                    $data["question_{$question->id}"] = $answer->attachments
                        ->pluck('file_path')
                        ->toArray();
                } elseif ($question->question_type === QuestionType::MULTIPLE_CHOICE) {
                    // Multiple choice expects an array
                    $data["question_{$question->id}"] = $answer->answer_value;
                } else {
                    // Single-value fields (TEXT, LONG_TEXT, BOOLEAN, SINGLE_CHOICE) expect a string
                    // answer_value is cast to array, so extract the first value or convert to string
                    $value = $answer->answer_value;
                    if (is_array($value)) {
                        $data["question_{$question->id}"] = $value[0] ?? null;
                    } else {
                        $data["question_{$question->id}"] = $value;
                    }
                }
                $data["comment_{$question->id}"] = $answer->comment;
            }
        }

        $this->data = $data;
        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        $questions = $this->record->template->questions()->orderBy('sort_order')->get();

        $schema = [];

        foreach ($questions as $index => $question) {
            $schema[] = $this->buildQuestionField($question, $index + 1);
        }

        return $form
            ->components([
                Section::make($this->record->template->title)
                    ->description(new HtmlString($this->record->template->description))
                    ->schema($schema),
            ])
            ->statePath('data');
    }

    protected function buildQuestionField(SurveyQuestion $question, int $number): Fieldset
    {
        $fieldName = "question_{$question->id}";
        $commentName = "comment_{$question->id}";

        $fields = [];

        $field = match ($question->question_type) {
            QuestionType::TEXT => TextInput::make($fieldName)
                ->label("{$number}. {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->maxLength(1000),

            QuestionType::LONG_TEXT => Textarea::make($fieldName)
                ->label("{$number}. {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->rows(4)
                ->maxLength(10000),

            QuestionType::BOOLEAN => Radio::make($fieldName)
                ->label("{$number}. {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options([
                    'yes' => __('Yes'),
                    'no' => __('No'),
                ])
                ->inline(),

            QuestionType::SINGLE_CHOICE => Radio::make($fieldName)
                ->label("{$number}. {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options($this->getOptionsFromQuestion($question)),

            QuestionType::MULTIPLE_CHOICE => CheckboxList::make($fieldName)
                ->label("{$number}. {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options($this->getOptionsFromQuestion($question)),

            QuestionType::FILE => FileUpload::make($fieldName)
                ->label("{$number}. {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->disk(config('filesystems.default'))
                ->directory('checklist-attachments')
                ->visibility('private')
                ->maxSize(10240),

            default => TextInput::make($fieldName)
                ->label("{$number}. {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required),
        };

        $fields[] = $field->columnSpanFull();

        if ($question->allow_comments) {
            $fields[] = Textarea::make($commentName)
                ->label(__('checklist.checklist.form.additional_comments'))
                ->placeholder(__('checklist.checklist.form.additional_comments_placeholder'))
                ->rows(2)
                ->maxLength(2000)
                ->columnSpanFull();
        }

        return Fieldset::make(__('checklist.checklist.form.item_number', ['number' => $number]))
            ->schema($fields)
            ->columnSpanFull();
    }

    protected function getOptionsFromQuestion(SurveyQuestion $question): array
    {
        $options = $question->options ?? [];
        $result = [];

        foreach ($options as $option) {
            $label = $option['label'] ?? $option;
            $result[$label] = $label;
        }

        return $result;
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (in_array($this->record->status, [SurveyStatus::DRAFT, SurveyStatus::SENT])) {
            $this->record->update(['status' => SurveyStatus::IN_PROGRESS]);
        }

        foreach ($this->record->template->questions as $question) {
            $fieldName = "question_{$question->id}";
            $commentName = "comment_{$question->id}";

            $answerValue = $data[$fieldName] ?? null;
            $comment = $data[$commentName] ?? null;

            if ($question->question_type === QuestionType::FILE) {
                $answer = SurveyAnswer::updateOrCreate(
                    [
                        'survey_id' => $this->record->id,
                        'survey_question_id' => $question->id,
                    ],
                    [
                        'answer_value' => null,
                        'comment' => $comment,
                    ]
                );

                $this->syncFileAttachments($answer, $answerValue);
            } else {
                SurveyAnswer::updateOrCreate(
                    [
                        'survey_id' => $this->record->id,
                        'survey_question_id' => $question->id,
                    ],
                    [
                        'answer_value' => $answerValue,
                        'comment' => $comment,
                    ]
                );
            }
        }

        Notification::make()
            ->title(__('checklist.checklist.notifications.progress_saved'))
            ->body(__('checklist.checklist.notifications.progress_saved_body'))
            ->success()
            ->send();
    }

    protected function syncFileAttachments(SurveyAnswer $answer, mixed $filePaths): void
    {
        $filePaths = is_array($filePaths) ? $filePaths : ($filePaths ? [$filePaths] : []);
        $disk = config('filesystems.default');

        $existingPaths = $answer->attachments->pluck('file_path')->toArray();

        foreach ($answer->attachments as $attachment) {
            if (! in_array($attachment->file_path, $filePaths)) {
                Storage::disk($disk)->delete($attachment->file_path);
                $attachment->delete();
            }
        }

        foreach ($filePaths as $filePath) {
            if (! in_array($filePath, $existingPaths) && Storage::disk($disk)->exists($filePath)) {
                SurveyAttachment::create([
                    'survey_answer_id' => $answer->id,
                    'file_name' => basename($filePath),
                    'file_path' => $filePath,
                    'file_size' => Storage::disk($disk)->size($filePath),
                    'uploaded_by' => auth()->id(),
                ]);
            }
        }
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        // Validate required questions
        $missingRequired = [];
        foreach ($this->record->template->questions as $question) {
            if ($question->is_required) {
                $fieldName = "question_{$question->id}";
                $value = $data[$fieldName] ?? null;

                if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                    $missingRequired[] = $question->question_text;
                }
            }
        }

        if (! empty($missingRequired)) {
            Notification::make()
                ->title(__('checklist.checklist.notifications.required_missing'))
                ->body(__('checklist.checklist.notifications.required_missing_body'))
                ->danger()
                ->send();

            return;
        }

        // Save all answers
        foreach ($this->record->template->questions as $question) {
            $fieldName = "question_{$question->id}";
            $commentName = "comment_{$question->id}";

            $answerValue = $data[$fieldName] ?? null;
            $comment = $data[$commentName] ?? null;

            if ($question->question_type === QuestionType::FILE) {
                $answer = SurveyAnswer::updateOrCreate(
                    [
                        'survey_id' => $this->record->id,
                        'survey_question_id' => $question->id,
                    ],
                    [
                        'answer_value' => null,
                        'comment' => $comment,
                    ]
                );

                $this->syncFileAttachments($answer, $answerValue);
            } else {
                SurveyAnswer::updateOrCreate(
                    [
                        'survey_id' => $this->record->id,
                        'survey_question_id' => $question->id,
                    ],
                    [
                        'answer_value' => $answerValue,
                        'comment' => $comment,
                    ]
                );
            }
        }

        // Mark as completed
        $this->record->update([
            'status' => SurveyStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        Notification::make()
            ->title(__('checklist.checklist.notifications.submitted'))
            ->body(__('checklist.checklist.notifications.submitted_body'))
            ->success()
            ->send();

        $this->redirect(ChecklistResource::getUrl('view', ['record' => $this->record]));
    }

    public function getTitle(): string
    {
        return $this->record->display_title;
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function getBreadcrumbs(): array
    {
        return [
            ChecklistResource::getUrl() => __('checklist.checklist.navigation.label'),
            ChecklistResource::getUrl('view', ['record' => $this->record]) => $this->record?->display_title ?? __('Checklist'),
            __('checklist.checklist.pages.respond.breadcrumb'),
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
            Action::make('save')
                ->label(__('checklist.checklist.actions.save_progress'))
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->action('save'),
            Action::make('submit')
                ->label(__('checklist.checklist.actions.submit'))
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->action('submit')
                ->requiresConfirmation()
                ->modalHeading(__('checklist.checklist.modals.submit.heading'))
                ->modalDescription(__('checklist.checklist.modals.submit.description'))
                ->modalSubmitActionLabel(__('checklist.checklist.modals.submit.confirm')),
        ];
    }
}
