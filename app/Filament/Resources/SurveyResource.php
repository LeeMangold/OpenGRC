<?php

namespace App\Filament\Resources;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Filament\Resources\SurveyResource\Pages\CreateSurvey;
use App\Filament\Resources\SurveyResource\Pages\EditSurvey;
use App\Filament\Resources\SurveyResource\Pages\ListSurveys;
use App\Filament\Resources\SurveyResource\Pages\RespondToSurveyInternal;
use App\Filament\Resources\SurveyResource\Pages\ScoreSurvey;
use App\Filament\Resources\SurveyResource\Pages\ViewSurvey;
use App\Filament\Resources\SurveyResource\RelationManagers\AnswersRelationManager;
use App\Mail\SurveyInvitationMail;
use App\Models\Survey;
use App\Models\User;
use App\Services\VendorRiskScoringService;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class SurveyResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|\UnitEnum|null $navigationGroup = 'Surveys';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('survey.survey.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('survey.survey.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('survey.survey.model.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Survey Details')
                    ->columns(2)
                    ->schema([
                        Select::make('survey_template_id')
                            ->label(__('survey.survey.form.template.label'))
                            ->relationship('template', 'title', fn (Builder $query) => $query->where('status', SurveyTemplateStatus::ACTIVE))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => request()->query('template'))
                            ->disabled(fn (?Survey $record): bool => $record !== null)
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->label(__('survey.survey.form.title.label'))
                            ->helperText(__('survey.survey.form.title.helper'))
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('survey.survey.form.status.label'))
                            ->options(SurveyStatus::class)
                            ->default(SurveyStatus::DRAFT)
                            ->required(),
                        Select::make('type')
                            ->label(__('Type'))
                            ->options(SurveyType::class)
                            ->default(SurveyType::VENDOR_ASSESSMENT)
                            ->required(),
                        RichEditor::make('description')
                            ->label(__('survey.survey.form.description.label'))
                            ->helperText(__('survey.survey.form.description.helper'))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                    ]),
                Section::make('Vendor')
                    ->schema([
                        Select::make('vendor_id')
                            ->label(__('Vendor'))
                            ->relationship('vendor', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Associate this survey with a vendor for TPRM tracking'),
                    ]),
                Section::make('Respondent Information')
                    ->description(__('survey.survey.form.respondent.description'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('respondent_email')
                            ->label(__('survey.survey.form.respondent_email.label'))
                            ->email()
                            ->maxLength(255)
                            ->helperText(__('survey.survey.form.respondent_email.helper')),
                        TextInput::make('respondent_name')
                            ->label(__('survey.survey.form.respondent_name.label'))
                            ->maxLength(255),
                        Select::make('assigned_to_id')
                            ->label(__('survey.survey.form.assigned_to.label'))
                            ->options(fn (string $operation): array => $operation === 'create' ? User::activeOptions() : User::optionsWithDeactivated())
                            ->searchable()
                            ->helperText(__('survey.survey.form.assigned_to.helper')),
                        DatePicker::make('due_date')
                            ->label(__('survey.survey.form.due_date.label'))
                            ->native(false),
                        DatePicker::make('expiration_date')
                            ->label(__('survey.survey.form.expiration_date.label'))
                            ->helperText(__('survey.survey.form.expiration_date.helper'))
                            ->native(false),
                    ]),
                Section::make('Survey Link')
                    ->description(fn (?Survey $record): string => $record?->isInternal()
                        ? __('Internal survey - accessible via admin panel')
                        : __('survey.survey.form.link.description'))
                    ->schema([
                        Placeholder::make('public_url')
                            ->label(fn (?Survey $record): string => $record?->isInternal()
                                ? __('Internal Assessment Link')
                                : __('survey.survey.form.link.label'))
                            ->content(fn (?Survey $record): string => $record === null
                                ? 'Link will be generated after saving'
                                : ($record->isInternal()
                                    ? static::getUrl('respond-internal', ['record' => $record])
                                    : $record->getPublicUrl()))
                            ->visible(fn (?Survey $record): bool => $record !== null),
                    ])
                    ->visible(fn (?Survey $record): bool => $record !== null),
                Hidden::make('created_by_id')
                    ->default(fn () => auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('display_title')
                    ->label(__('survey.survey.table.columns.title'))
                    ->searchable(['title'])
                    ->sortable(['title'])
                    ->wrap(),
                TextColumn::make('template.title')
                    ->label(__('survey.survey.table.columns.template'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-')
                    ->url(fn (Survey $record) => $record->vendor_id ? VendorResource::getUrl('view', ['record' => $record->vendor_id]) : null),
                TextColumn::make('respondent_display')
                    ->label(__('survey.survey.table.columns.respondent'))
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('survey.survey.table.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('progress')
                    ->label(__('survey.survey.table.columns.progress'))
                    ->suffix('%')
                    ->color(fn (Survey $record): string => match (true) {
                        $record->progress >= 100 => 'success',
                        $record->progress >= 50 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('due_date')
                    ->label(__('survey.survey.table.columns.due_date'))
                    ->date()
                    ->sortable()
                    ->color(fn (Survey $record): ?string => $record->isExpired() ? 'danger' : null),
                TextColumn::make('completed_at')
                    ->label(__('survey.survey.table.columns.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('createdBy.name')
                    ->label(__('survey.survey.table.columns.created_by'))
                    ->formatStateUsing(fn ($record): string => $record->createdBy?->displayName() ?? '')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('survey.survey.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SurveyStatus::class)
                    ->label(__('survey.survey.table.filters.status')),
                SelectFilter::make('type')
                    ->options(SurveyType::class)
                    ->label(__('Type')),
                SelectFilter::make('vendor_id')
                    ->relationship('vendor', 'name')
                    ->label('Vendor')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('survey_template_id')
                    ->relationship('template', 'title')
                    ->label(__('survey.survey.table.filters.template'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('assigned_to_id')
                    ->relationship('assignedTo', 'name')
                    ->label(__('survey.survey.table.filters.assigned_to'))
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('copy_link')
                        ->label(fn (Survey $record): string => $record->isInternal()
                            ? __('Open Assessment')
                            : __('survey.survey.actions.copy_link'))
                        ->icon('heroicon-o-link')
                        ->color('gray')
                        ->url(fn (Survey $record): ?string => $record->isInternal()
                            ? static::getUrl('respond-internal', ['record' => $record])
                            : null)
                        ->requiresConfirmation(fn (Survey $record): bool => ! $record->isInternal())
                        ->modalHeading('Survey Link')
                        ->modalDescription(fn (Survey $record) => 'Copy this link to share the survey: '.$record->getPublicUrl())
                        ->modalSubmitActionLabel('Copy to Clipboard')
                        ->action(fn () => null)
                        ->visible(fn (Survey $record): bool => $record->isInternal() || $record->access_token !== null),
                    Action::make('mark_complete')
                        ->label(__('survey.survey.actions.mark_complete'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Survey $record) {
                            $record->update([
                                'status' => SurveyStatus::COMPLETED,
                                'completed_at' => now(),
                            ]);
                        })
                        ->visible(fn (Survey $record): bool => ! in_array($record->status, [SurveyStatus::COMPLETED, SurveyStatus::EXPIRED])),
                    Action::make('send_invitation')
                        ->label(__('survey.survey.actions.send_invitation'))
                        ->icon('heroicon-o-envelope')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading(__('survey.survey.actions.send_invitation_modal.heading'))
                        ->modalDescription(fn (Survey $record) => __('survey.survey.actions.send_invitation_modal.description', ['email' => $record->respondent_email]))
                        ->modalSubmitActionLabel(__('survey.survey.actions.send_invitation_modal.submit'))
                        ->action(function (Survey $record) {
                            // Update status to SENT regardless of email success
                            $record->update(['status' => SurveyStatus::SENT]);

                            try {
                                Mail::send(new SurveyInvitationMail($record));

                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_sent.title'))
                                    ->body(__('survey.survey.notifications.invitation_sent.body', ['email' => $record->respondent_email]))
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title(__('Survey Sent'))
                                    ->body(__('Survey marked as sent but email notification failed: ').$e->getMessage())
                                    ->warning()
                                    ->send();
                            }
                        })
                        ->visible(fn (Survey $record): bool => ! empty($record->respondent_email) && $record->status === SurveyStatus::DRAFT),
                    Action::make('resend_invitation')
                        ->label(__('survey.survey.actions.resend_invitation'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading(__('survey.survey.actions.resend_invitation_modal.heading'))
                        ->modalDescription(fn (Survey $record) => __('survey.survey.actions.resend_invitation_modal.description', ['email' => $record->respondent_email]))
                        ->modalSubmitActionLabel(__('survey.survey.actions.resend_invitation_modal.submit'))
                        ->action(function (Survey $record) {
                            try {
                                Mail::send(new SurveyInvitationMail($record));

                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_sent.title'))
                                    ->body(__('survey.survey.notifications.invitation_sent.body', ['email' => $record->respondent_email]))
                                    ->success()
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title(__('survey.survey.notifications.invitation_failed.title'))
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Survey $record): bool => ! empty($record->respondent_email) && in_array($record->status, [SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])),
                    Action::make('score_survey')
                        ->label(__('Score Survey'))
                        ->icon('heroicon-o-clipboard-document-check')
                        ->color('primary')
                        ->url(fn (Survey $record): string => static::getUrl('score', ['record' => $record]))
                        ->visible(fn (Survey $record): bool => in_array($record->status, [SurveyStatus::PENDING_SCORING, SurveyStatus::COMPLETED])),
                    Action::make('recalculate_score')
                        ->label(__('Recalculate Risk Score'))
                        ->icon('heroicon-o-calculator')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription(__('This will recalculate the risk score based on current answers and question weights.'))
                        ->action(function (Survey $record) {
                            $service = new VendorRiskScoringService;
                            $score = $service->calculateSurveyScore($record);

                            if ($record->vendor) {
                                $service->calculateVendorScore($record->vendor);
                            }

                            Notification::make()
                                ->title(__('Risk score recalculated'))
                                ->body(__('New score: :score/100', ['score' => $score]))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Survey $record): bool => $record->status === SurveyStatus::COMPLETED),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('survey.survey.table.empty_state.heading'))
            ->emptyStateDescription(__('survey.survey.table.empty_state.description'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('survey.survey.infolist.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('display_title')
                            ->label(__('survey.survey.form.title.label')),
                        TextEntry::make('template.title')
                            ->label(__('survey.survey.form.template.label'))
                            ->url(fn (Survey $record) => SurveyTemplateResource::getUrl('view', ['record' => $record->template])),
                        TextEntry::make('status')
                            ->label(__('survey.survey.form.status.label'))
                            ->badge(),
                        TextEntry::make('type')
                            ->label(__('Type'))
                            ->badge(),
                        TextEntry::make('vendor.name')
                            ->label('Vendor')
                            ->placeholder('-')
                            ->url(fn (Survey $record) => $record->vendor_id ? VendorResource::getUrl('view', ['record' => $record->vendor_id]) : null),
                        TextEntry::make('respondent_display')
                            ->label(__('survey.survey.table.columns.respondent')),
                        TextEntry::make('assignedTo.name')
                            ->label(__('survey.survey.form.assigned_to.label'))
                            ->formatStateUsing(fn ($record): string => $record->assignedTo?->displayName() ?? '-'),
                        TextEntry::make('due_date')
                            ->label(__('survey.survey.form.due_date.label'))
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('expiration_date')
                            ->label(__('survey.survey.form.expiration_date.label'))
                            ->date()
                            ->placeholder('-')
                            ->color(fn (Survey $record): ?string => $record->isLinkExpired() ? 'danger' : null),
                        TextEntry::make('progress')
                            ->label(__('survey.survey.table.columns.progress'))
                            ->suffix('%'),
                        TextEntry::make('completed_at')
                            ->label(__('survey.survey.table.columns.completed_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('risk_score')
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
                            ->formatStateUsing(fn (?int $state): string => $state !== null ? "{$state}/100" : '-'),
                        TextEntry::make('createdBy.name')
                            ->label(__('survey.survey.table.columns.created_by'))
                            ->formatStateUsing(fn ($record): string => $record->createdBy?->displayName() ?? ''),
                        TextEntry::make('public_url')
                            ->label(fn (Survey $record): string => $record->isInternal()
                                ? __('Internal Assessment Link')
                                : __('survey.survey.form.link.label'))
                            ->state(fn (Survey $record): string => $record->isInternal()
                                ? static::getUrl('respond-internal', ['record' => $record])
                                : $record->getPublicUrl())
                            ->copyable()
                            ->copyMessage('Link copied!')
                            ->url(fn (Survey $record): ?string => $record->isInternal()
                                ? static::getUrl('respond-internal', ['record' => $record])
                                : null)
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label(__('survey.survey.form.description.label'))
                            ->html()
                            ->columnSpanFull()
                            ->hidden(fn (Survey $record): bool => empty($record->description)),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSurveys::route('/'),
            'create' => CreateSurvey::route('/create'),
            'view' => ViewSurvey::route('/{record}'),
            'edit' => EditSurvey::route('/{record}/edit'),
            'score' => ScoreSurvey::route('/{record}/score'),
            'respond-internal' => RespondToSurveyInternal::route('/{record}/respond'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
