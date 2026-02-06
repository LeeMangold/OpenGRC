<?php

namespace App\Filament\Resources;

use App\Enums\StandardStatus;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Exports\StandardExporter;
use App\Filament\Resources\StandardResource\Pages\CreateStandard;
use App\Filament\Resources\StandardResource\Pages\EditStandard;
use App\Filament\Resources\StandardResource\Pages\ListStandards;
use App\Filament\Resources\StandardResource\Pages\ViewStandard;
use App\Filament\Resources\StandardResource\RelationManagers\AuditsRelationManager;
use App\Filament\Resources\StandardResource\RelationManagers\ControlsRelationManager;
use App\Models\Standard;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class StandardResource extends Resource
{
    use HasTaxonomyFields;

    protected static ?string $model = Standard::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('standard.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('standard.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('standard.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('standard.model.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('name')
                    ->autofocus()
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('standard.form.name.placeholder'))
                    ->hint(__('standard.form.name.hint'))
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('standard.form.name.tooltip')),
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->placeholder(__('standard.form.code.placeholder'))
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('standard.form.code.tooltip'))
                    ->unique(Standard::class, 'code', ignoreRecord: true)
                    ->live()
                    ->afterStateUpdated(function ($livewire, $component) {
                        $livewire->validateOnly($component->getStatePath());
                    }),
                TextInput::make('authority')
                    ->required()
                    ->maxLength(255)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('standard.form.authority.tooltip'))
                    ->placeholder(__('standard.form.authority.placeholder')),
                Select::make('status')
                    ->default(StandardStatus::DRAFT)
                    ->required()
                    ->enum(StandardStatus::class)
                    ->options(StandardStatus::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('standard.form.status.tooltip'))
                    ->native(false),
                self::taxonomySelect('Department', 'department')
                    ->nullable()
                    ->columnSpan(1),
                self::taxonomySelect('Scope', 'scope')
                    ->nullable()
                    ->columnSpan(1),
                TextInput::make('reference_url')
                    ->columnSpan(1)
                    ->maxLength(255)
                    ->url()
                    ->placeholder(__('standard.form.reference_url.placeholder'))
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('standard.form.reference_url.tooltip')),
                RichEditor::make('description')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles',
                    ])
                    ->maxLength(65535)
                    ->required()
                    ->hint(__('standard.form.description.hint'))
                    ->placeholder(__('standard.form.description.placeholder')),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('code')
                    ->label(__('standard.table.columns.code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('standard.table.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->wrap(true),
                TextColumn::make('description')
                    ->label(__('standard.table.columns.description'))
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->wrap(true)
                    ->limit(250),
                TextColumn::make('authority')
                    ->label(__('standard.table.columns.authority'))
                    ->searchable()
                    ->sortable()
                    ->wrap(true),
                TextColumn::make('status')
                    ->label(__('standard.table.columns.status'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(StandardStatus::class)
                    ->label(__('standard.table.filters.status')),
                SelectFilter::make('authority')
                    ->options(Standard::pluck('authority', 'authority')->toArray())
                    ->label(__('standard.table.filters.authority')),
                TrashedFilter::make(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(StandardExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make()->hiddenLabel(),
                ActionGroup::make([
                    Action::make('set_in_scope')
                        ->label(__('standard.table.actions.set_in_scope.label'))
                        ->icon('heroicon-o-check-circle')
                        ->modalHeading(__('standard.table.actions.set_in_scope.modal_heading'))
                        ->modalContent(new HtmlString(__('standard.table.actions.set_in_scope.modal_content')))
                        ->modalSubmitActionLabel(__('standard.table.actions.set_in_scope.submit_label'))
                        ->hidden(
                            fn ($record) => $record->status === StandardStatus::IN_SCOPE
                        )
                        ->action(fn ($record) => $record->update(['status' => StandardStatus::IN_SCOPE])),
                    Action::make('set_out_scope')
                        ->label(__('standard.table.actions.set_out_scope.label'))
                        ->icon('heroicon-o-check-circle')
                        ->modalHeading(__('standard.table.actions.set_out_scope.modal_heading'))
                        ->modalContent(new HtmlString(__('standard.table.actions.set_out_scope.modal_content')))
                        ->modalSubmitActionLabel(__('standard.table.actions.set_out_scope.submit_label'))
                        ->hidden(
                            fn ($record) => $record->status !== StandardStatus::IN_SCOPE
                        )
                        ->action(fn ($record) => $record->update(['status' => StandardStatus::DRAFT])),
                    EditAction::make(),
                    DeleteAction::make(),
                    RestoreAction::make(),
                ])->label(__('standard.table.actions.group_label')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(StandardExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(new HtmlString(__('standard.table.empty_state.heading')))
            ->emptyStateDescription(
                new HtmlString(__('standard.table.empty_state.description', [
                    'url' => route('filament.app.resources.standards.index'),
                ]))
            );
    }

    public static function getRelations(): array
    {
        return [
            ControlsRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStandards::route('/'),
            'create' => CreateStandard::route('/create'),
            'view' => ViewStandard::route('/{record}'),
            'edit' => EditStandard::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    // This is the view page for a single Standard record
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('standard.infolist.section_title'))
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('code'),
                        TextEntry::make('authority'),
                        TextEntry::make('status'),
                        TextEntry::make('taxonomies')
                            ->label('Department')
                            ->getStateUsing(function (Standard $record) {
                                return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('taxonomies')
                            ->label('Scope')
                            ->getStateUsing(function (Standard $record) {
                                return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->html(),
                    ]),
            ]);
    }

    /**
     * @param  Standard  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->code.'-'.$record->name;
    }

    /**
     * @param  Standard  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return StandardResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Standard  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Standard' => $record->name,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'name', 'description', 'authority'];
    }
}
