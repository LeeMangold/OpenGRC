<?php

namespace App\Filament\Resources;

use App\Enums\StandardStatus;
use App\Filament\Resources\StandardResource\Pages;
use App\Filament\Resources\StandardResource\RelationManagers;
use App\Models\Standard;
use Exception;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class StandardResource extends Resource
{
    protected static ?string $model = Standard::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

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

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
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
                TextInput::make('reference_url')
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->url()
                    ->placeholder(__('standard.form.reference_url.placeholder'))
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('standard.form.reference_url.tooltip')),
                RichEditor::make('description')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
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
            ->description(new class implements \Illuminate\Contracts\Support\Htmlable
            {
                public function toHtml()
                {
                    return "<div class='fi-section-content p-6'>" . 
                        __('standard.table.description') . 
                        "</div>";
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('standard.table.columns.code'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('standard.table.columns.name'))
                    ->searchable()
                    ->sortable()
                    ->wrap(true),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('standard.table.columns.description'))
                    ->html()
                    ->searchable()
                    ->sortable()
                    ->wrap(true)
                    ->limit(250),
                Tables\Columns\TextColumn::make('authority')
                    ->label(__('standard.table.columns.authority'))
                    ->searchable()
                    ->sortable()
                    ->wrap(true),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('standard.table.columns.status'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(StandardStatus::class)
                    ->label(__('standard.table.filters.status')),
                Tables\Filters\SelectFilter::make('authority')
                    ->options(Standard::pluck('authority', 'authority')->toArray())
                    ->label(__('standard.table.filters.authority')),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->hiddenLabel(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('set_in_scope')
                        ->label(__('standard.table.actions.set_in_scope.label'))
                        ->icon('heroicon-o-check-circle')
                        ->modalHeading(__('standard.table.actions.set_in_scope.modal_heading'))
                        ->modalContent(new HtmlString(__('standard.table.actions.set_in_scope.modal_content')))
                        ->modalSubmitActionLabel(__('standard.table.actions.set_in_scope.submit_label'))
                        ->hidden(
                            fn ($record) => $record->status === StandardStatus::IN_SCOPE
                        )
                        ->action(fn ($record) => $record->update(['status' => StandardStatus::IN_SCOPE])),
                    Tables\Actions\Action::make('set_out_scope')
                        ->label(__('standard.table.actions.set_out_scope.label'))
                        ->icon('heroicon-o-check-circle')
                        ->modalHeading(__('standard.table.actions.set_out_scope.modal_heading'))
                        ->modalContent(new HtmlString(__('standard.table.actions.set_out_scope.modal_content')))
                        ->modalSubmitActionLabel(__('standard.table.actions.set_out_scope.submit_label'))
                        ->hidden(
                            fn ($record) => $record->status !== StandardStatus::IN_SCOPE
                        )
                        ->action(fn ($record) => $record->update(['status' => StandardStatus::DRAFT])),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])->label(__('standard.table.actions.group_label')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(new HtmlString(__('standard.table.empty_state.heading')))
            ->emptyStateDescription(
                new HtmlString(__('standard.table.empty_state.description', [
                    'url' => route('filament.app.resources.bundles.index')
                ]))
            );
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ControlsRelationManager::class,
            RelationManagers\AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStandards::route('/'),
            'create' => Pages\CreateStandard::route('/create'),
            'view' => Pages\ViewStandard::route('/{record}'),
            'edit' => Pages\EditStandard::route('/{record}/edit'),
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
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('standard.infolist.section_title'))
                    ->columns(4)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('code'),
                        TextEntry::make('authority'),
                        TextEntry::make('status'),
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
