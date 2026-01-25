<?php

namespace App\Filament\Resources\SurveyResource\Pages;

use App\Enums\QuestionType;
use App\Enums\SurveyStatus;
use App\Filament\Resources\SurveyResource;
use App\Filament\Resources\VendorResource;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Services\VendorRiskScoringService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class ScoreSurvey extends Page implements HasForms, HasInfolists
{
    use InteractsWithForms;
    use InteractsWithInfolists;
    use InteractsWithRecord;

    protected static string $resource = SurveyResource::class;

    protected static string $view = 'filament.pages.score-survey';

    public ?array $data = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load(['template.questions', 'answers.question', 'vendor']);

        // Only allow scoring completed or pending_scoring surveys
        if (! in_array($this->record->status, [SurveyStatus::COMPLETED, SurveyStatus::PENDING_SCORING])) {
            Notification::make()
                ->title(__('Survey not ready for scoring'))
                ->body(__('Only completed or pending scoring surveys can be scored.'))
                ->warning()
                ->send();

            $this->redirect(SurveyResource::getUrl('view', ['record' => $this->record]));

            return;
        }

        $this->loadExistingScores();
    }

    protected function loadExistingScores(): void
    {
        $data = [];

        foreach ($this->record->answers as $answer) {
            $data["score_{$answer->id}"] = $answer->manual_score;
        }

        $this->form->fill($data);
    }

    public function surveyInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Survey Information')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('display_title')
                            ->label('Survey'),
                        TextEntry::make('vendor.name')
                            ->label('Vendor')
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('risk_score')
                            ->label('Current Risk Score')
                            ->badge()
                            ->color(fn (?int $state): string => match (true) {
                                $state === null => 'gray',
                                $state <= 20 => 'success',
                                $state <= 40 => 'info',
                                $state <= 60 => 'warning',
                                $state <= 80 => 'orange',
                                default => 'danger',
                            })
                            ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}/100" : 'Not calculated'),
                    ]),
            ]);
    }

    public function autoScoredInfolist(Infolist $infolist): Infolist
    {
        $autoScoredAnswers = $this->getAutoScoredAnswers();
        $breakdown = $this->getSurveyScoreBreakdown();

        if ($autoScoredAnswers->isEmpty()) {
            return $infolist->schema([]);
        }

        $entries = [];
        foreach ($autoScoredAnswers as $answer) {
            $question = $answer->question;
            $itemBreakdown = collect($breakdown)->firstWhere('question_id', $question->id);
            $answerScore = $itemBreakdown['score'] ?? 0;

            $entries[] = Grid::make(5)
                ->schema([
                    TextEntry::make("question_{$answer->id}")
                        ->label('Question')
                        ->state(\Illuminate\Support\Str::limit($question->question_text, 60))
                        ->columnSpan(2),
                    TextEntry::make("type_{$answer->id}")
                        ->label('Type')
                        ->state($question->question_type->getLabel())
                        ->badge()
                        ->color('gray'),
                    TextEntry::make("answer_{$answer->id}")
                        ->label('Answer')
                        ->state($this->formatAnswerValueForDisplay($answer->answer_value)),
                    TextEntry::make("score_{$answer->id}")
                        ->label('Assessment')
                        ->state($this->getAssessmentLabel($answerScore))
                        ->badge()
                        ->color(fn (): string => $this->getAssessmentColor($answerScore)),
                ]);
        }

        return $infolist
            ->state([])
            ->schema([
                Section::make('Auto-Scored Questions')
                    ->description('These questions are automatically scored based on the answer type and risk impact settings.')
                    ->schema($entries),
            ]);
    }

    public function breakdownInfolist(Infolist $infolist): Infolist
    {
        $breakdown = $this->getSurveyScoreBreakdown();

        if (empty($breakdown) || $this->record->risk_score === null) {
            return $infolist->schema([]);
        }

        $entries = [];
        foreach ($breakdown as $item) {
            $score = $item['score'];
            $isNA = $item['is_na'] ?? false;

            $entries[] = Grid::make(4)
                ->schema([
                    TextEntry::make("q_{$item['question_id']}")
                        ->label('Question')
                        ->state(\Illuminate\Support\Str::limit($item['question_text'], 50))
                        ->columnSpan(2),
                    TextEntry::make("w_{$item['question_id']}")
                        ->label('Weight')
                        ->state($isNA ? '-' : "{$item['weight']}%"),
                    TextEntry::make("s_{$item['question_id']}")
                        ->label('Assessment')
                        ->state($this->getAssessmentLabel($score))
                        ->badge()
                        ->color($this->getAssessmentColor($score)),
                ]);
        }

        $entries[] = Grid::make(4)
            ->schema([
                TextEntry::make('total_label')
                    ->label('')
                    ->state('Total Risk Score')
                    ->weight('bold')
                    ->columnSpan(3),
                TextEntry::make('total_score')
                    ->label('')
                    ->state($this->getAssessmentLabel($this->record->risk_score)." ({$this->record->risk_score}/100)")
                    ->badge()
                    ->size('lg')
                    ->color($this->getAssessmentColor($this->record->risk_score)),
            ]);

        return $infolist
            ->state([])
            ->schema([
                Section::make('Score Breakdown')
                    ->description('Detailed breakdown of how the risk score was calculated.')
                    ->schema($entries),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {
        $schema = [];

        // Get questions that need manual scoring (text/long_text with risk_weight > 0)
        $answers = $this->record->answers()
            ->with('question')
            ->get()
            ->filter(function ($answer) {
                $question = $answer->question;

                return $question
                    && in_array($question->question_type, [QuestionType::TEXT, QuestionType::LONG_TEXT])
                    && $question->risk_weight > 0;
            });

        if ($answers->isEmpty()) {
            $schema[] = Forms\Components\Placeholder::make('no_manual_scoring')
                ->label('')
                ->content('This survey has no open-ended questions that require manual scoring. All questions can be automatically scored.')
                ->columnSpanFull();

            return $schema;
        }

        foreach ($answers as $answer) {
            $question = $answer->question;

            $schema[] = Forms\Components\Section::make($question->question_text)
                ->description("Weight: {$question->risk_weight}%")
                ->schema([
                    Forms\Components\Placeholder::make("answer_preview_{$answer->id}")
                        ->label('Answer')
                        ->content(fn () => $this->formatAnswerValue($answer->answer_value))
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make("comment_preview_{$answer->id}")
                        ->label('Additional Comment')
                        ->content($answer->comment ?? 'No comment')
                        ->visible(fn () => ! empty($answer->comment))
                        ->columnSpanFull(),
                    Forms\Components\ToggleButtons::make("score_{$answer->id}")
                        ->label('Assessment')
                        ->options([
                            0 => 'Pass',
                            50 => 'Partial',
                            100 => 'Fail',
                            -1 => 'N/A',
                        ])
                        ->icons([
                            0 => 'heroicon-o-check-circle',
                            50 => 'heroicon-o-minus-circle',
                            100 => 'heroicon-o-x-circle',
                            -1 => 'heroicon-o-no-symbol',
                        ])
                        ->colors([
                            0 => 'success',
                            50 => 'warning',
                            100 => 'danger',
                            -1 => 'gray',
                        ])
                        ->inline()
                        ->required()
                        ->live(),
                ]);
        }

        return $schema;
    }

    protected function formatAnswerValue(mixed $value): string
    {
        if ($value === null) {
            return 'No answer provided';
        }

        if (is_array($value)) {
            if (isset($value['value'])) {
                return (string) $value['value'];
            }

            return implode(', ', array_filter($value, fn ($v) => ! is_array($v)));
        }

        return (string) $value;
    }

    protected function formatAnswerValueForDisplay(mixed $value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_array($value)) {
            if (isset($value['value'])) {
                return (string) $value['value'];
            }

            return implode(', ', array_filter($value, fn ($v) => ! is_array($v)));
        }

        if ($value === true || $value === 1 || $value === '1') {
            return 'Yes';
        }

        if ($value === false || $value === 0 || $value === '0') {
            return 'No';
        }

        return (string) $value;
    }

    protected function getAssessmentLabel(?int $score): string
    {
        if ($score === null || $score === -1) {
            return 'N/A';
        }

        return match (true) {
            $score === 0 => 'Pass',
            $score <= 50 => 'Partial',
            default => 'Fail',
        };
    }

    protected function getAssessmentColor(?int $score): string
    {
        if ($score === null || $score === -1) {
            return 'gray';
        }

        return match (true) {
            $score === 0 => 'success',
            $score <= 50 => 'warning',
            default => 'danger',
        };
    }

    public function getTitle(): string
    {
        return 'Score Survey: '.($this->record?->display_title ?? 'Survey');
    }

    public function getBreadcrumbs(): array
    {
        // If this survey is associated with a vendor, navigate back to vendor
        if ($this->record->vendor_id) {
            return [
                VendorResource::getUrl() => __('Vendors'),
                VendorResource::getUrl('view', ['record' => $this->record->vendor_id]) => $this->record->vendor?->name ?? __('Vendor'),
                SurveyResource::getUrl('view', ['record' => $this->record]) => $this->record?->display_title ?? 'Survey',
                'Score',
            ];
        }

        return [
            SurveyResource::getUrl() => 'Surveys',
            SurveyResource::getUrl('view', ['record' => $this->record]) => $this->record?->display_title ?? 'Survey',
            'Score',
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save_scores')
                ->label(__('Save Scores'))
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->action('saveScoresOnly')
                ->visible(fn () => $this->record->status === SurveyStatus::PENDING_SCORING),
            Actions\Action::make('complete_assessment')
                ->label(__('Complete Assessment'))
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('Complete Assessment'))
                ->modalDescription(__('This will save all scores, calculate the risk score, and mark the assessment as complete. Are you sure you want to proceed?'))
                ->modalSubmitActionLabel(__('Yes, complete assessment'))
                ->action('completeAssessment')
                ->visible(fn () => $this->record->status === SurveyStatus::PENDING_SCORING),
            Actions\Action::make('recalculate')
                ->label(__('Recalculate Risk Score'))
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->action('saveAndCalculate')
                ->visible(fn () => $this->record->status === SurveyStatus::COMPLETED),
            Actions\Action::make('back')
                ->label(__('Back to Survey'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(SurveyResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    protected function saveScores(): int
    {
        $data = $this->form->getState();

        $updated = 0;

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'score_') && $value !== null) {
                $answerId = (int) str_replace('score_', '', $key);
                $answer = SurveyAnswer::find($answerId);

                if ($answer && $answer->survey_id === $this->record->id) {
                    $answer->update([
                        'manual_score' => (int) $value,
                        'scored_by' => Auth::id(),
                        'scored_at' => now(),
                    ]);
                    $updated++;
                }
            }
        }

        return $updated;
    }

    public function saveScoresOnly(): void
    {
        $updated = $this->saveScores();

        Notification::make()
            ->title(__('Scores saved'))
            ->body(__(':count answer(s) scored successfully.', ['count' => $updated]))
            ->success()
            ->send();
    }

    public function completeAssessment(): void
    {
        // Save the scores
        $this->saveScores();

        // Update status to completed
        $this->record->update(['status' => SurveyStatus::COMPLETED]);

        // Calculate the overall risk score
        $service = new VendorRiskScoringService;
        $score = $service->calculateSurveyScore($this->record);

        // Also update vendor score if linked
        if ($this->record->vendor) {
            $service->calculateVendorScore($this->record->vendor);
        }

        $recommendedRating = $service->recommendRiskRating($score);

        Notification::make()
            ->title(__('Assessment completed'))
            ->body(__('Survey risk score: :score/100. Risk rating: :rating', [
                'score' => $score,
                'rating' => $recommendedRating->getLabel(),
            ]))
            ->success()
            ->send();

        // Redirect to view page
        $this->redirect(SurveyResource::getUrl('view', ['record' => $this->record]));
    }

    public function saveAndCalculate(): void
    {
        // Save the scores
        $this->saveScores();

        // Calculate the overall risk score
        $service = new VendorRiskScoringService;
        $score = $service->calculateSurveyScore($this->record);

        // Also update vendor score if linked
        if ($this->record->vendor) {
            $service->calculateVendorScore($this->record->vendor);
        }

        $recommendedRating = $service->recommendRiskRating($score);

        Notification::make()
            ->title(__('Risk score recalculated'))
            ->body(__('Survey risk score: :score/100. Recommended rating: :rating', [
                'score' => $score,
                'rating' => $recommendedRating->getLabel(),
            ]))
            ->success()
            ->send();

        // Refresh the record to show updated score
        $this->record->refresh();
    }

    public function getSurveyScoreBreakdown(): array
    {
        $service = new VendorRiskScoringService;

        return $service->getScoreBreakdown($this->record);
    }

    public function getAutoScoredAnswers(): \Illuminate\Support\Collection
    {
        return $this->record->answers()
            ->with('question')
            ->get()
            ->filter(function ($answer) {
                $question = $answer->question;

                return $question
                    && ! in_array($question->question_type, [QuestionType::TEXT, QuestionType::LONG_TEXT])
                    && $question->risk_weight > 0;
            });
    }

    public function hasManualScoringQuestions(): bool
    {
        return $this->record->answers()
            ->with('question')
            ->get()
            ->filter(function ($answer) {
                $question = $answer->question;

                return $question
                    && in_array($question->question_type, [QuestionType::TEXT, QuestionType::LONG_TEXT])
                    && $question->risk_weight > 0;
            })
            ->isNotEmpty();
    }
}
