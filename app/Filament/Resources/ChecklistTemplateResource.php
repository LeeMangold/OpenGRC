<?php

namespace App\Filament\Resources;

use App\Enums\QuestionType;
use App\Enums\RecurrenceFrequency;
use App\Enums\SurveyTemplateStatus;
use App\Enums\SurveyType;
use App\Filament\Resources\ChecklistTemplateResource\Pages\CreateChecklistTemplate;
use App\Filament\Resources\ChecklistTemplateResource\Pages\EditChecklistTemplate;
use App\Filament\Resources\ChecklistTemplateResource\Pages\ListChecklistTemplates;
use App\Filament\Resources\ChecklistTemplateResource\Pages\ViewChecklistTemplate;
use App\Filament\Resources\ChecklistTemplateResource\RelationManagers\ChecklistsRelationManager;
use App\Models\SurveyTemplate;
use App\Policies\ChecklistTemplatePolicy;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChecklistTemplateResource extends Resource
{
    protected static ?string $model = SurveyTemplate::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Entities';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $slug = 'checklist-templates';

    protected static ?string $policy = ChecklistTemplatePolicy::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('checklist.template.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('checklist.template.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('checklist.template.model.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('checklist.template.form.details_section'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label(__('checklist.template.form.title.label'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->label(__('checklist.template.form.description.label'))
                            ->disableToolbarButtons(['attachFiles'])
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label(__('checklist.template.form.status.label'))
                            ->options(SurveyTemplateStatus::class)
                            ->default(SurveyTemplateStatus::DRAFT)
                            ->required(),
                        Hidden::make('type')
                            ->default(SurveyType::INTERNAL_CHECKLIST),
                        Hidden::make('created_by_id')
                            ->default(fn () => auth()->id()),
                    ]),
                Section::make(__('checklist.template.form.assignment_section'))
                    ->columns(2)
                    ->schema([
                        Select::make('default_assignee_id')
                            ->label(__('checklist.template.form.default_assignee.label'))
                            ->relationship('defaultAssignee', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText(__('checklist.template.form.default_assignee.helper')),
                    ]),
                Section::make(__('checklist.template.form.recurrence_section'))
                    ->columns(2)
                    ->description(__('checklist.template.form.recurrence_description'))
                    ->schema([
                        Select::make('recurrence_frequency')
                            ->label(__('checklist.template.form.recurrence_frequency.label'))
                            ->options(RecurrenceFrequency::class)
                            ->live()
                            ->helperText(__('checklist.template.form.recurrence_frequency.helper')),
                        TextInput::make('recurrence_interval')
                            ->label(__('checklist.template.form.recurrence_interval.label'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(365)
                            ->visible(fn (Get $get): bool => $get('recurrence_frequency') !== null)
                            ->helperText(__('checklist.template.form.recurrence_interval.helper')),
                        Select::make('recurrence_day_of_week')
                            ->label(__('checklist.template.form.recurrence_day_of_week.label'))
                            ->options([
                                0 => __('Sunday'),
                                1 => __('Monday'),
                                2 => __('Tuesday'),
                                3 => __('Wednesday'),
                                4 => __('Thursday'),
                                5 => __('Friday'),
                                6 => __('Saturday'),
                            ])
                            ->visible(fn (Get $get): bool => $get('recurrence_frequency') === RecurrenceFrequency::WEEKLY->value || $get('recurrence_frequency') === RecurrenceFrequency::WEEKLY)
                            ->helperText(__('checklist.template.form.recurrence_day_of_week.helper')),
                        TextInput::make('recurrence_day_of_month')
                            ->label(__('checklist.template.form.recurrence_day_of_month.label'))
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->visible(fn (Get $get): bool => $get('recurrence_frequency') === RecurrenceFrequency::MONTHLY->value || $get('recurrence_frequency') === RecurrenceFrequency::MONTHLY)
                            ->helperText(__('checklist.template.form.recurrence_day_of_month.helper')),
                    ]),
                Section::make(__('checklist.template.form.items_section'))
                    ->description(__('checklist.template.form.items_description'))
                    ->schema([
                        Placeholder::make('items_locked_notice')
                            ->label('')
                            ->content(__('checklist.template.form.items_locked.message'))
                            ->visible(fn (?SurveyTemplate $record): bool => $record?->isLocked() ?? false),
                        Repeater::make('questions')
                            ->relationship()
                            ->label('')
                            ->orderColumn('sort_order')
                            ->reorderable(fn (?SurveyTemplate $record): bool => ! ($record?->isLocked() ?? false))
                            ->collapsible()
                            ->cloneable(fn (?SurveyTemplate $record): bool => ! ($record?->isLocked() ?? false))
                            ->deletable(fn (?SurveyTemplate $record): bool => ! ($record?->isLocked() ?? false))
                            ->addable(fn (?SurveyTemplate $record): bool => ! ($record?->isLocked() ?? false))
                            ->itemLabel(fn (array $state): ?string => $state['question_text'] ?? __('checklist.template.form.new_item'))
                            ->schema([
                                TextInput::make('question_text')
                                    ->label(__('checklist.template.form.item_text.label'))
                                    ->required()
                                    ->maxLength(1000)
                                    ->columnSpanFull()
                                    ->disabled(fn ($livewire): bool => $livewire->getRecord()?->isLocked() ?? false),
                                Select::make('question_type')
                                    ->label(__('checklist.template.form.item_type.label'))
                                    ->options([
                                        QuestionType::BOOLEAN->value => __('Yes/No Checkbox'),
                                        QuestionType::TEXT->value => __('Short Text'),
                                        QuestionType::LONG_TEXT->value => __('Long Text'),
                                        QuestionType::SINGLE_CHOICE->value => __('Single Choice'),
                                        QuestionType::MULTIPLE_CHOICE->value => __('Multiple Choice'),
                                        QuestionType::FILE->value => __('File Upload'),
                                    ])
                                    ->default(QuestionType::BOOLEAN)
                                    ->required()
                                    ->live(onBlur: false)
                                    ->afterStateUpdated(fn (Set $set) => $set('options', []))
                                    ->disabled(fn ($livewire): bool => $livewire->getRecord()?->isLocked() ?? false),
                                Toggle::make('is_required')
                                    ->label(__('checklist.template.form.is_required.label'))
                                    ->default(true)
                                    ->disabled(fn ($livewire): bool => $livewire->getRecord()?->isLocked() ?? false),
                                Toggle::make('allow_comments')
                                    ->label(__('checklist.template.form.allow_comments.label'))
                                    ->helperText(__('checklist.template.form.allow_comments.helper'))
                                    ->default(false)
                                    ->disabled(fn ($livewire): bool => $livewire->getRecord()?->isLocked() ?? false),
                                TextInput::make('help_text')
                                    ->label(__('checklist.template.form.help_text.label'))
                                    ->maxLength(500)
                                    ->columnSpanFull()
                                    ->disabled(fn ($livewire): bool => $livewire->getRecord()?->isLocked() ?? false),
                                Repeater::make('options')
                                    ->label(__('checklist.template.form.options.label'))
                                    ->schema([
                                        TextInput::make('label')
                                            ->label(__('checklist.template.form.option_label'))
                                            ->required()
                                            ->disabled(fn ($livewire): bool => $livewire->getRecord()?->isLocked() ?? false),
                                    ])
                                    ->columns(1)
                                    ->addActionLabel(__('checklist.template.form.add_option'))
                                    ->addable(fn ($livewire): bool => ! ($livewire->getRecord()?->isLocked() ?? false))
                                    ->deletable(fn ($livewire): bool => ! ($livewire->getRecord()?->isLocked() ?? false))
                                    ->reorderable(fn ($livewire): bool => ! ($livewire->getRecord()?->isLocked() ?? false))
                                    ->visible(function (Get $get): bool {
                                        $type = $get('question_type');
                                        if ($type instanceof QuestionType) {
                                            $type = $type->value;
                                        }

                                        return in_array($type, [
                                            QuestionType::SINGLE_CHOICE->value,
                                            QuestionType::MULTIPLE_CHOICE->value,
                                        ]);
                                    })
                                    ->minItems(function (Get $get): int {
                                        $type = $get('question_type');
                                        if ($type instanceof QuestionType) {
                                            $type = $type->value;
                                        }

                                        return in_array($type, [
                                            QuestionType::SINGLE_CHOICE->value,
                                            QuestionType::MULTIPLE_CHOICE->value,
                                        ]) ? 2 : 0;
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel(__('checklist.template.form.add_item')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('title')
                    ->label(__('checklist.template.table.columns.title'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label(__('checklist.template.table.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->label(__('checklist.template.table.columns.items_count'))
                    ->counts('questions')
                    ->sortable(),
                TextColumn::make('surveys_count')
                    ->label(__('checklist.template.table.columns.checklists_count'))
                    ->counts('surveys')
                    ->sortable(),
                TextColumn::make('defaultAssignee.name')
                    ->label(__('checklist.template.table.columns.default_assignee'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('recurrence_frequency')
                    ->label(__('checklist.template.table.columns.recurrence'))
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('createdBy.name')
                    ->label(__('checklist.template.table.columns.created_by'))
                    ->formatStateUsing(fn ($record): string => $record->createdBy?->displayName() ?? '')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('checklist.template.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(SurveyTemplateStatus::class)
                    ->label(__('checklist.template.table.filters.status')),
                SelectFilter::make('recurrence_frequency')
                    ->options(RecurrenceFrequency::class)
                    ->label(__('checklist.template.table.filters.recurrence')),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('create_checklist')
                        ->label(__('checklist.template.actions.create_checklist'))
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->url(fn (SurveyTemplate $record): string => ChecklistResource::getUrl('create', ['template' => $record->id]))
                        ->visible(fn (SurveyTemplate $record): bool => $record->status === SurveyTemplateStatus::ACTIVE),
                    Action::make('duplicate')
                        ->label(__('checklist.template.actions.duplicate'))
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->action(function (SurveyTemplate $record) {
                            $newTemplate = $record->replicate();
                            $newTemplate->title = $record->title.' ('.__('Copy').')';
                            $newTemplate->status = SurveyTemplateStatus::DRAFT;
                            $newTemplate->created_by_id = auth()->id();
                            $newTemplate->last_checklist_generated_at = null;
                            $newTemplate->next_checklist_due_at = null;
                            $newTemplate->save();

                            foreach ($record->questions as $question) {
                                /** @var \App\Models\SurveyQuestion $question */
                                $newQuestion = $question->replicate();
                                $newQuestion->survey_template_id = $newTemplate->id;
                                $newQuestion->save();
                            }

                            return redirect(ChecklistTemplateResource::getUrl('edit', ['record' => $newTemplate]));
                        }),
                    DeleteAction::make()
                        ->hidden(fn (SurveyTemplate $record): bool => $record->hasChecklists()),
                    ForceDeleteAction::make()
                        ->hidden(fn (SurveyTemplate $record): bool => $record->hasChecklists()),
                    RestoreAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->hidden(fn (): bool => true), // Disabled - use row actions instead
                    ForceDeleteBulkAction::make()
                        ->hidden(fn (): bool => true), // Disabled - use row actions instead
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('checklist.template.table.empty_state.heading'))
            ->emptyStateDescription(__('checklist.template.table.empty_state.description'));
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('checklist.template.infolist.section_title'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('title')
                            ->label(__('checklist.template.form.title.label'))
                            ->columnSpanFull(),
                        TextEntry::make('status')
                            ->label(__('checklist.template.form.status.label'))
                            ->badge(),
                        TextEntry::make('defaultAssignee.name')
                            ->label(__('checklist.template.form.default_assignee.label'))
                            ->formatStateUsing(fn ($record): string => $record->defaultAssignee?->displayName() ?? ''),
                        TextEntry::make('createdBy.name')
                            ->label(__('checklist.template.table.columns.created_by'))
                            ->formatStateUsing(fn ($record): string => $record->createdBy?->displayName() ?? ''),
                        TextEntry::make('recurrence_frequency')
                            ->label(__('checklist.template.form.recurrence_frequency.label'))
                            ->badge(),
                        TextEntry::make('recurrence_interval')
                            ->label(__('checklist.template.form.recurrence_interval.label')),
                        TextEntry::make('next_checklist_due_at')
                            ->label(__('checklist.template.infolist.next_due'))
                            ->dateTime(),
                        TextEntry::make('description')
                            ->label(__('checklist.template.form.description.label'))
                            ->html()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ChecklistsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChecklistTemplates::route('/'),
            'create' => CreateChecklistTemplate::route('/create'),
            'view' => ViewChecklistTemplate::route('/{record}'),
            'edit' => EditChecklistTemplate::route('/{record}/edit'),
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
