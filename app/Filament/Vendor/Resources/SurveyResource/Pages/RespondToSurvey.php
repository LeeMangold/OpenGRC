<?php

namespace App\Filament\Vendor\Resources\SurveyResource\Pages;

use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Filament\Vendor\Resources\SurveyResource;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyAttachment;
use App\Models\SurveyQuestion;
use App\Notifications\DropdownNotification;
use App\Services\VendorRiskScoringService;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class RespondToSurvey extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SurveyResource::class;

    protected string $view = 'filament.vendor.pages.respond-to-survey';

    public Survey|Model|int|string|null $record = null;

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        // Use Filament's record resolution method instead of direct find
        $this->record = $this->resolveRecord($record);

        // Verify vendor access - user must be the respondent OR belong to the vendor
        $vendorUser = Auth::guard('vendor')->user();
        $isRespondent = $this->record->respondent_email === $vendorUser?->email;
        $isVendorMember = $this->record->vendor_id === $vendorUser?->vendor_id;

        if (! $isRespondent && ! $isVendorMember) {
            abort(403);
        }

        // Check if survey can be responded to
        if (! in_array($this->record->status, [SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])) {
            Notification::make()
                ->title('Survey cannot be modified')
                ->body('This survey has already been completed or is not available for response.')
                ->warning()
                ->send();

            $this->redirect(SurveyResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        // Load existing answers
        $this->loadExistingAnswers();
    }

    protected function resolveRecord(int|string $key): Model
    {
        // Bypass the resource's getEloquentQuery and find directly
        // We'll verify access manually in mount()
        return Survey::findOrFail($key);
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
                // For file questions, load attachment paths
                if ($question->question_type === QuestionType::FILE) {
                    $data["question_{$question->id}"] = $answer->attachments
                        ->pluck('file_path')
                        ->toArray();
                } else {
                    $data["question_{$question->id}"] = $answer->answer_value;
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
                    ->description(new \Illuminate\Support\HtmlString($this->record->template->description))
                    ->schema($schema),
            ])
            ->statePath('data');
    }

    protected function buildQuestionField(SurveyQuestion $question, int $number): Fieldset
    {
        $fieldName = "question_{$question->id}";
        $commentName = "comment_{$question->id}";

        $fields = [];

        // Main question field based on type
        $field = match ($question->question_type) {
            QuestionType::TEXT => TextInput::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->maxLength(1000),

            QuestionType::LONG_TEXT => Textarea::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->rows(4)
                ->maxLength(10000),

            QuestionType::BOOLEAN => Radio::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options([
                    'yes' => 'Yes',
                    'no' => 'No',
                ])
                ->inline(),

            QuestionType::SINGLE_CHOICE => Radio::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options($this->getOptionsFromQuestion($question)),

            QuestionType::MULTIPLE_CHOICE => CheckboxList::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->options($this->getOptionsFromQuestion($question)),

            QuestionType::FILE => FileUpload::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required)
                ->disk(config('filesystems.default'))
                ->directory('survey-attachments')
                ->visibility('private')
                ->maxSize(10240), // 10MB

            default => TextInput::make($fieldName)
                ->label("Q{$number}: {$question->question_text}")
                ->helperText($question->help_text)
                ->required($question->is_required),
        };

        $fields[] = $field->columnSpanFull();

        // Add comment field if allowed
        if ($question->allow_comments) {
            $fields[] = Textarea::make($commentName)
                ->label('Additional Comments')
                ->placeholder('Add any additional context or notes...')
                ->rows(2)
                ->maxLength(2000)
                ->columnSpanFull();
        }

        return Fieldset::make("Question {$number}")
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

        // Update status to in progress if it was sent
        if ($this->record->status === SurveyStatus::SENT) {
            $this->record->update(['status' => SurveyStatus::IN_PROGRESS]);
        }

        // Save each answer
        foreach ($this->record->template->questions as $question) {
            $fieldName = "question_{$question->id}";
            $commentName = "comment_{$question->id}";

            $answerValue = $data[$fieldName] ?? null;
            $comment = $data[$commentName] ?? null;

            // Handle file uploads separately
            if ($question->question_type === QuestionType::FILE) {
                $answer = SurveyAnswer::updateOrCreate(
                    [
                        'survey_id' => $this->record->id,
                        'survey_question_id' => $question->id,
                    ],
                    [
                        'answer_value' => null, // File references stored in attachments table
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
            ->title('Progress saved')
            ->body('Your responses have been saved. You can continue later.')
            ->success()
            ->send();
    }

    /**
     * Sync file attachments for a survey answer.
     */
    protected function syncFileAttachments(SurveyAnswer $answer, mixed $filePaths): void
    {
        $filePaths = is_array($filePaths) ? $filePaths : ($filePaths ? [$filePaths] : []);
        $disk = config('filesystems.default');

        // Get current attachment paths
        $existingPaths = $answer->attachments->pluck('file_path')->toArray();

        // Delete attachments that are no longer in the form
        foreach ($answer->attachments as $attachment) {
            if (! in_array($attachment->file_path, $filePaths)) {
                // Delete the file from storage
                Storage::disk($disk)->delete($attachment->file_path);
                $attachment->delete();
            }
        }

        // Add new attachments
        foreach ($filePaths as $filePath) {
            if (! in_array($filePath, $existingPaths) && Storage::disk($disk)->exists($filePath)) {
                SurveyAttachment::create([
                    'survey_answer_id' => $answer->id,
                    'file_name' => basename($filePath),
                    'file_path' => $filePath,
                    'file_size' => Storage::disk($disk)->size($filePath),
                    'uploaded_by' => null, // Vendor user, not a User model
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
                ->title('Required questions not answered')
                ->body('Please answer all required questions before submitting.')
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

            // Handle file uploads separately
            if ($question->question_type === QuestionType::FILE) {
                $answer = SurveyAnswer::updateOrCreate(
                    [
                        'survey_id' => $this->record->id,
                        'survey_question_id' => $question->id,
                    ],
                    [
                        'answer_value' => null, // File references stored in attachments table
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

        // Check if there are TEXT/LONG_TEXT questions with risk_weight > 0 that need manual scoring
        $requiresManualScoring = $this->record->template->questions()
            ->whereIn('question_type', [QuestionType::TEXT, QuestionType::LONG_TEXT])
            ->where('risk_weight', '>', 0)
            ->exists();

        if ($requiresManualScoring) {
            // Set status to pending scoring - admin will need to score manually
            $this->record->update([
                'status' => SurveyStatus::PENDING_SCORING,
                'completed_at' => now(),
            ]);
        } else {
            // No manual scoring needed - mark as completed and calculate score
            $this->record->update([
                'status' => SurveyStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            // Calculate risk score
            $scoringService = new VendorRiskScoringService;
            $scoringService->calculateSurveyScore($this->record);

            // Also update vendor's overall risk score
            if ($this->record->vendor) {
                $scoringService->calculateVendorScore($this->record->vendor);
            }
        }

        // Notify the user who created/sent the survey
        if ($this->record->createdBy) {
            $surveyTitle = $this->record->display_title;
            $respondentName = $this->record->respondent_name ?? $this->record->respondent_email ?? 'A vendor';

            // Build URL manually since we're in the vendor panel context
            $actionUrl = $requiresManualScoring
                ? url("/app/surveys/{$this->record->id}/score")
                : url("/app/surveys/{$this->record->id}");

            $this->record->createdBy->notify(new DropdownNotification(
                title: 'Survey Submitted',
                body: "{$respondentName} has submitted the survey: {$surveyTitle}",
                icon: $requiresManualScoring ? 'heroicon-o-clipboard-document-check' : 'heroicon-o-check-circle',
                color: $requiresManualScoring ? 'warning' : 'success',
                actionUrl: $actionUrl,
                actionLabel: $requiresManualScoring ? 'Score Survey' : 'View Survey'
            ));
        }

        Notification::make()
            ->title(__('Survey submitted'))
            ->body(__('Thank you! Your survey response has been submitted successfully.'))
            ->success()
            ->send();

        $this->redirect(SurveyResource::getUrl('view', ['record' => $this->record]));
    }

    public function getTitle(): string
    {
        return 'Respond to Survey';
    }

    public function getSubheading(): ?string
    {
        return $this->record->template->title ?? '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Back to Surveys')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(SurveyResource::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Progress')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->action('save'),
            Action::make('submit')
                ->label('Submit Survey')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->action('submit')
                ->requiresConfirmation()
                ->modalHeading('Submit Survey')
                ->modalDescription('Are you sure you want to submit this survey? You will not be able to make changes after submission.')
                ->modalSubmitActionLabel('Yes, submit survey'),
        ];
    }
}
