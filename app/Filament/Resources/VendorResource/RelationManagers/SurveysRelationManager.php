<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Filament\Resources\SurveyResource;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Services\VendorAssessmentService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class SurveysRelationManager extends RelationManager
{
    protected static string $relationship = 'surveys';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('survey_template_id')
                    ->label(__('Survey Template'))
                    ->options(SurveyTemplate::where('status', SurveyTemplateStatus::ACTIVE)->pluck('title', 'id'))
                    ->searchable()
                    ->required()
                    ->disabled(fn (?Survey $record): bool => $record !== null),
                TextInput::make('respondent_email')
                    ->label(__('Respondent Email'))
                    ->email()
                    ->required(),
                TextInput::make('respondent_name')
                    ->label(__('Respondent Name')),
                DatePicker::make('due_date')
                    ->label(__('Due Date'))
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['template'])->where(function ($q) {
                $q->where('type', SurveyType::VENDOR_ASSESSMENT)
                    ->orWhereNull('type');
            }))
            ->columns([
                TextColumn::make('display_title')
                    ->label(__('survey.survey.table.columns.title'))
                    ->sortable(['title']),
                TextColumn::make('template.title')
                    ->label(__('survey.survey.table.columns.template'))
                    ->sortable(),
                TextColumn::make('respondent_display')
                    ->label(__('survey.survey.table.columns.respondent'))
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('survey.survey.table.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('progress')
                    ->label(__('survey.survey.table.columns.progress'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label(__('survey.survey.table.columns.due_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('risk_score')
                    ->label('Risk Score')
                    ->badge()
                    ->color(fn (?int $state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 20 => 'success',
                        $state <= 40 => 'info',
                        $state <= 60 => 'warning',
                        $state <= 80 => 'orange',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}/100" : '-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('survey.survey.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SurveyStatus::class),
            ])
            ->headerActions([
                Action::make('assess_risk')
                    ->label(__('Assess Risk'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('primary')
                    ->schema(VendorAssessmentService::getAssessRiskFormSchema())
                    ->action(fn (array $data) => VendorAssessmentService::handleAssessRisk($this->ownerRecord, $data)),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => SurveyResource::getUrl('view', ['record' => $record])),
                Action::make('resend_invitation')
                    ->label(__('Resend'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Survey $record) {
                        try {
                            Mail::send(new SurveyInvitationMail($record));

                            Notification::make()
                                ->title(__('Survey Sent'))
                                ->body(__('Survey invitation sent to :email', ['email' => $record->respondent_email]))
                                ->success()
                                ->send();
                        } catch (Exception $e) {
                            Notification::make()
                                ->title(__('Failed to Send Survey'))
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn (Survey $record): bool => ! empty($record->respondent_email) && in_array($record->status, [SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
