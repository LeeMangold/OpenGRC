<?php

namespace App\Filament\Resources;

use App\Enums\SurveyStatus;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Filament\Resources\ChecklistResource\Pages\ApproveChecklist;
use App\Filament\Resources\ChecklistResource\Pages\CreateChecklist;
use App\Filament\Resources\ChecklistResource\Pages\EditChecklist;
use App\Filament\Resources\ChecklistResource\Pages\ListChecklists;
use App\Filament\Resources\ChecklistResource\Pages\RespondToChecklist;
use App\Filament\Resources\ChecklistResource\Pages\ViewChecklist;
use App\Models\Survey;
use App\Models\SurveyTemplate;
use App\Models\User;
use App\Policies\ChecklistPolicy;
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
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\IconEntry;
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

class ChecklistResource extends Resource
{
    protected static ?string $model = Survey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Entities';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'checklists';

    protected static ?string $policy = ChecklistPolicy::class;

    public static function getNavigationLabel(): string
    {
        return __('checklist.checklist.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('checklist.checklist.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('checklist.checklist.model.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('checklist.checklist.form.details_section'))
                    ->columns(2)
                    ->schema([
                        Select::make('survey_template_id')
                            ->label(__('checklist.checklist.form.template.label'))
                            ->relationship(
                                'template',
                                'title',
                                fn (Builder $query) => $query
                                    ->where('status', SurveyTemplateStatus::ACTIVE)
                                    ->where('type', SurveyType::INTERNAL_CHECKLIST)
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn () => request()->query('template'))
                            ->disabled(fn (?Survey $record): bool => $record !== null)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $template = SurveyTemplate::find($state);
                                    if ($template && $template->default_assignee_id) {
                                        $set('assigned_to_id', $template->default_assignee_id);
                                    }
                                }
                            })
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('title')
                            ->label(__('checklist.checklist.form.title.label'))
                            ->helperText(__('checklist.checklist.form.title.helper'))
                            ->maxLength(255),
                        Select::make('status')
                            ->label(__('checklist.checklist.form.status.label'))
                            ->options(SurveyStatus::class)
                            ->default(SurveyStatus::DRAFT)
                            ->required(),
                        Hidden::make('type')
                            ->default(SurveyType::INTERNAL_CHECKLIST),
                        RichEditor::make('description')
                            ->label(__('checklist.checklist.form.description.label'))
                            ->helperText(__('checklist.checklist.form.description.helper'))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                    ]),
                Section::make(__('checklist.checklist.form.assignment_section'))
                    ->columns(2)
                    ->schema([
                        Select::make('assigned_to_id')
                            ->label(__('checklist.checklist.form.assigned_to.label'))
                            ->options(User::whereNotNull('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->disabled(fn (?Survey $record): bool => $record?->status === SurveyStatus::COMPLETED)
                            ->helperText(__('checklist.checklist.form.assigned_to.helper')),
                        Select::make('approver_id')
                            ->label(__('checklist.checklist.form.approver.label'))
                            ->options(User::whereNotNull('name')->pluck('name', 'id'))
                            ->searchable()
                            ->helperText(__('checklist.checklist.form.approver.helper')),
                        DatePicker::make('due_date')
                            ->label(__('checklist.checklist.form.due_date.label'))
                            ->native(false),
                    ]),
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
                    ->label(__('checklist.checklist.table.columns.title'))
                    ->description(fn (Survey $record) => "{$record->template->title} Template")
                    ->searchable(['title'])
                    ->sortable(['title'])
                    ->wrap(),
                TextColumn::make('assignedTo.name')
                    ->label(__('checklist.checklist.table.columns.assigned_to'))
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label(__('checklist.checklist.table.columns.status'))
                    ->badge()
                    ->sortable()
                    ->description(fn (Survey $record): ?string => $record->status === SurveyStatus::COMPLETED && $record->assignedTo
                        ? "by {$record->assignedTo->name}"
                        : null),
                TextColumn::make('progress')
                    ->label(__('checklist.checklist.table.columns.progress'))
                    ->suffix('%')
                    ->color(fn (Survey $record): string => match (true) {
                        $record->progress >= 100 => 'success',
                        $record->progress >= 50 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('approval_status')
                    ->label(__('checklist.checklist.table.columns.approved'))
                    ->state(fn (Survey $record): string => $record->isApproved() ? __('Yes') : __('No'))
                    ->icon(fn (Survey $record): string => $record->isApproved() ? 'heroicon-o-check-badge' : 'heroicon-o-x-circle')
                    ->color(fn (Survey $record): string => $record->isApproved() ? 'success' : 'gray')
                    ->description(fn (Survey $record): ?string => $record->isApproved()
                        ? "by {$record->latestApproval->approver_name} on {$record->latestApproval->approved_at->format('M j, Y')}"
                        : null),
                TextColumn::make('due_date')
                    ->label(__('checklist.checklist.table.columns.due_date'))
                    ->date()
                    ->sortable()
                    ->color(fn (Survey $record): ?string => $record->isExpired() ? 'danger' : null),
                TextColumn::make('completed_at')
                    ->label(__('checklist.checklist.table.columns.completed_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label(__('checklist.checklist.table.columns.created_by'))
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('checklist.checklist.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SurveyStatus::class)
                    ->label(__('checklist.checklist.table.filters.status')),
                SelectFilter::make('survey_template_id')
                    ->relationship('template', 'title', fn (Builder $query) => $query->where('type', SurveyType::INTERNAL_CHECKLIST))
                    ->label(__('checklist.checklist.table.filters.template'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('assigned_to_id')
                    ->relationship('assignedTo', 'name')
                    ->label(__('checklist.checklist.table.filters.assigned_to'))
                    ->searchable()
                    ->preload(),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('complete_checklist')
                        ->label(__('checklist.checklist.actions.complete'))
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->url(fn (Survey $record): string => static::getUrl('respond', ['record' => $record]))
                        ->visible(fn (Survey $record): bool => in_array($record->status, [SurveyStatus::DRAFT, SurveyStatus::SENT, SurveyStatus::IN_PROGRESS])),
                    Action::make('approve_checklist')
                        ->label(__('checklist.checklist.actions.approve'))
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->url(fn (Survey $record): string => static::getUrl('approve', ['record' => $record]))
                        ->visible(fn (Survey $record): bool => $record->status === SurveyStatus::COMPLETED
                            && ! $record->isApproved()
                            && $record->canBeApprovedBy(auth()->user())),
                    Action::make('mark_complete')
                        ->label(__('checklist.checklist.actions.mark_complete'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Survey $record) {
                            $record->update([
                                'status' => SurveyStatus::COMPLETED,
                                'completed_at' => now(),
                            ]);

                            Notification::make()
                                ->title(__('checklist.checklist.notifications.completed'))
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Survey $record): bool => ! in_array($record->status, [SurveyStatus::COMPLETED, SurveyStatus::EXPIRED])),
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
            ->emptyStateHeading(__('checklist.checklist.table.empty_state.heading'))
            ->emptyStateDescription(__('checklist.checklist.table.empty_state.description'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('checklist.checklist.infolist.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('display_title')
                            ->label(__('checklist.checklist.form.title.label')),
                        TextEntry::make('template.title')
                            ->label(__('checklist.checklist.form.template.label'))
                            ->url(fn (Survey $record) => ChecklistTemplateResource::getUrl('view', ['record' => $record->template])),
                        TextEntry::make('status')
                            ->label(__('checklist.checklist.form.status.label'))
                            ->badge(),
                        TextEntry::make('assignedTo.name')
                            ->label(__('checklist.checklist.form.assigned_to.label'))
                            ->default('-'),
                        TextEntry::make('approver.name')
                            ->label(__('checklist.checklist.form.approver.label'))
                            ->placeholder(__('checklist.checklist.infolist.no_approver_assigned')),
                        TextEntry::make('due_date')
                            ->label(__('checklist.checklist.form.due_date.label'))
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('progress')
                            ->label(__('checklist.checklist.table.columns.progress'))
                            ->suffix('%'),
                        TextEntry::make('completed_at')
                            ->label(__('checklist.checklist.table.columns.completed_at'))
                            ->dateTime()
                            ->placeholder('-'),
                        IconEntry::make('is_approved')
                            ->label(__('checklist.checklist.infolist.approved'))
                            ->state(fn (Survey $record): bool => $record->isApproved())
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-x-circle'),
                    ]),
                Section::make(__('checklist.checklist.infolist.general_section'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('createdBy.name')
                            ->label(__('checklist.checklist.table.columns.created_by')),
                        TextEntry::make('description')
                            ->label(__('checklist.checklist.form.description.label'))
                            ->html()
                            ->columnSpanFull()
                            ->hidden(fn (Survey $record): bool => empty($record->description)),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChecklists::route('/'),
            'create' => CreateChecklist::route('/create'),
            'view' => ViewChecklist::route('/{record}'),
            'edit' => EditChecklist::route('/{record}/edit'),
            'respond' => RespondToChecklist::route('/{record}/respond'),
            'approve' => ApproveChecklist::route('/{record}/approve'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', SurveyType::INTERNAL_CHECKLIST)
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
