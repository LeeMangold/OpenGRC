<?php

namespace App\Filament\Resources\SurveyResource\RelationManagers;

use App\Enums\QuestionType;
use App\Models\SurveyAnswer;
use App\Services\VendorRiskScoringService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Answers are typically view-only in the admin panel
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('question.question_text')
                    ->label(__('survey.survey.answers.columns.question'))
                    ->wrap()
                    ->limit(100),
                TextColumn::make('question.question_type')
                    ->label(__('survey.survey.answers.columns.type'))
                    ->badge(),
                TextColumn::make('display_value')
                    ->label(__('survey.survey.answers.columns.answer'))
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $value = $record->answer_value;
                        $questionType = $record->question?->question_type;

                        // Handle FILE questions first - they store data in attachments, not answer_value
                        if ($questionType === QuestionType::FILE) {
                            $attachmentCount = $record->attachments()->count();

                            return $attachmentCount > 0
                                ? $attachmentCount.' file(s) attached'
                                : new HtmlString('<span class="text-gray-400">No files</span>');
                        }

                        if ($value === null) {
                            return new HtmlString('<span class="text-gray-400">No answer</span>');
                        }

                        if ($questionType === QuestionType::BOOLEAN) {
                            return match ($record->booleanValue()) {
                                true => __('Yes'),
                                false => __('No'),
                                null => (string) $record->display_value,
                            };
                        }

                        if (is_array($value)) {
                            if (isset($value['value'])) {
                                return $value['value'];
                            }

                            return implode(', ', array_filter($value, fn ($v) => ! is_array($v)));
                        }

                        return (string) $value;
                    }),
                TextColumn::make('comment')
                    ->label(__('survey.survey.answers.columns.comment'))
                    ->wrap()
                    ->limit(50)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('calculated_score')
                    ->label(__('Assessment'))
                    ->badge()
                    ->state(function (SurveyAnswer $record): ?int {
                        return $this->calculateAnswerScore($record);
                    })
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state === -1 => 'gray',
                        $state === 0 => 'success',
                        $state <= 50 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?int $state): string => match (true) {
                        $state === null => __('Not Scored'),
                        $state === -1 => __('N/A'),
                        $state === 0 => __('Pass'),
                        $state <= 50 => __('Partial'),
                        default => __('Fail'),
                    })
                    ->visible(fn ($livewire) => in_array($livewire->getOwnerRecord()->status->value, ['completed', 'pending_scoring'])),
                TextColumn::make('updated_at')
                    ->label(__('survey.survey.answers.columns.answered_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                Action::make('score_answer')
                    ->label('Score')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->schema([
                        Placeholder::make('answer_preview')
                            ->label('Answer')
                            ->content(fn (SurveyAnswer $record): string => is_array($record->answer_value)
                                ? implode(', ', array_filter($record->answer_value, fn ($v) => ! is_array($v)))
                                : (string) ($record->answer_value ?? 'No answer')),
                        TextInput::make('manual_score')
                            ->label('Risk Score (0-100)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required()
                            ->default(fn (SurveyAnswer $record) => $record->manual_score)
                            ->helperText('0 = No risk (best), 100 = High risk (worst)'),
                    ])
                    ->action(function (SurveyAnswer $record, array $data) {
                        $record->update([
                            'manual_score' => $data['manual_score'],
                            'scored_by' => Auth::id(),
                            'scored_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Answer scored')
                            ->body("Score set to {$data['manual_score']}/100")
                            ->success()
                            ->send();
                    })
                    ->visible(fn (SurveyAnswer $record): bool => in_array($record->question?->question_type, [QuestionType::TEXT, QuestionType::LONG_TEXT])
                        && $record->question?->risk_weight > 0
                    ),
                ViewAction::make()
                    ->modalHeading(fn ($record) => 'Answer Details')
                    ->modalContent(function ($record) {
                        $question = $record->question;
                        $value = $record->answer_value;

                        $html = '<div class="space-y-4">';
                        $html .= '<div><strong>Question:</strong><br>'.e($question->question_text ?? 'Unknown').'</div>';
                        $html .= '<div><strong>Type:</strong> '.($question->question_type?->getLabel() ?? 'Unknown').'</div>';
                        $html .= '<div><strong>Required:</strong> '.($question->is_required ? 'Yes' : 'No').'</div>';

                        if ($question->help_text) {
                            $html .= '<div><strong>Help Text:</strong><br>'.e($question->help_text).'</div>';
                        }

                        $html .= '<hr class="my-4">';
                        $html .= '<div><strong>Answer:</strong><br>';

                        // Handle FILE questions first - they store data in attachments, not answer_value
                        if ($question->question_type === QuestionType::FILE) {
                            // Show file attachments with download links
                            if ($record->attachments->count() > 0) {
                                $html .= '<div class="space-y-2 mt-2">';
                                foreach ($record->attachments as $attachment) {
                                    $downloadUrl = route('survey-attachment.download', $attachment);
                                    $html .= '<div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-800 p-2 rounded">';
                                    $html .= '<svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                                    $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>';
                                    $html .= '</svg>';
                                    $html .= '<div class="flex-1">';
                                    $html .= '<a href="'.e($downloadUrl).'" class="text-primary-600 hover:text-primary-800 font-medium" target="_blank">';
                                    $html .= e($attachment->file_name);
                                    $html .= '</a>';
                                    $html .= '<span class="text-xs text-gray-500 ml-2">('.$attachment->formatted_file_size.')</span>';
                                    $html .= '</div>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                            } else {
                                $html .= '<span class="text-gray-400">No files uploaded</span>';
                            }
                        } elseif ($value === null) {
                            $html .= '<span class="text-gray-400">No answer provided</span>';
                        } elseif ($question->question_type === QuestionType::BOOLEAN) {
                            $html .= e(match ($record->booleanValue()) {
                                true => __('Yes'),
                                false => __('No'),
                                null => (string) $record->display_value,
                            });
                        } elseif (is_array($value)) {
                            $html .= '<ul class="list-disc list-inside">';
                            foreach ($value as $v) {
                                if (! is_array($v)) {
                                    $html .= '<li>'.e($v).'</li>';
                                }
                            }
                            $html .= '</ul>';
                        } else {
                            $html .= e($value);
                        }

                        $html .= '</div>';

                        // Show comment if present
                        if ($record->comment) {
                            $html .= '<hr class="my-4">';
                            $html .= '<div><strong>Additional Comments:</strong><br>';
                            $html .= '<div class="bg-gray-50 dark:bg-gray-800 p-3 rounded mt-1">'.nl2br(e($record->comment)).'</div>';
                            $html .= '</div>';
                        }

                        $html .= '</div>';

                        return new HtmlString($html);
                    }),
            ])
            ->toolbarActions([
                //
            ])
            ->emptyStateHeading('No answers yet')
            ->emptyStateDescription('Answers will appear here once the respondent starts filling out the survey.');
    }

    protected function calculateAnswerScore(SurveyAnswer $record): ?int
    {
        $question = $record->question;

        if (! $question || $question->risk_weight <= 0) {
            return null;
        }

        $service = new VendorRiskScoringService;

        return $service->getAnswerScore($question, $record);
    }
}
