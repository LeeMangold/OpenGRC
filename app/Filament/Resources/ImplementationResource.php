<?php

namespace App\Filament\Resources;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use App\Filament\Resources\ImplementationResource\Pages;
use App\Filament\Resources\ImplementationResource\RelationManagers;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\User;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ImplementationResource extends Resource
{
    protected static ?string $model = Implementation::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('implementation.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('implementation.navigation.group');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->maxLength(255)
                    ->required()
                    ->unique(Implementation::class, 'code', ignoreRecord: true)
                    ->live()
                    ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, Forms\Components\TextInput $component) {
                        $livewire->validateOnly($component->getStatePath());
                    })
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a unique code for this implementation. This code will be used to identify this implementation in the system.'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->label('Implementation Status')
                    ->enum(ImplementationStatus::class)
                    ->options(ImplementationStatus::class)
                    ->default(ImplementationStatus::UNKNOWN)
                    ->native(false)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select the the best implementation level for this implementation. This can be assessed and changed later.'),

                Forms\Components\Select::make('controls')
                    ->label('Related Controls')
                    ->relationship('controls', 'code')
                    ->options(
                        Control::all()->mapWithKeys(function ($control) {
                            return [$control->id => "($control->code) - $control->title"];
                        })->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->placeholder('Select related controls') // Optional: Adds a placeholder
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: "All implementations should relate to a control. If you don't have a relevant control in place, consider creating a new one first."),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255)
                    ->required()
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a title for this implementation.'),
                Forms\Components\RichEditor::make('details')
                    ->required()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a description for this implementation. This be an in-depth description of how this implementation is put in place.'),

                Forms\Components\RichEditor::make('notes')
                    ->maxLength(65535)
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Any additional internal notes. This is never visible to an auditor.'),

                Forms\Components\Select::make('implementation_owner_id')
                    ->label('Owner')
                    ->options(User::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable()
                    ->columnSpan(1),
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
                        __('implementation.table.description') . 
                        "</div>";
                }
            })
            ->emptyStateHeading(__('implementation.table.empty_state.heading'))
            ->emptyStateDescription(__('implementation.table.empty_state.description'))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('implementation.table.columns.code'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('implementation.table.columns.title'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('effectiveness')
                    ->label(__('implementation.table.columns.effectiveness'))
                    ->getStateUsing(fn ($record) => $record->getEffectiveness())
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('last_assessed')
                    ->label(__('implementation.table.columns.last_assessed'))
                    ->getStateUsing(fn ($record) => $record->getEffectivenessDate() ? $record->getEffectivenessDate() : 'Not yet audited')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('implementation.table.columns.status'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('implementationOwner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('implementation.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('implementation.table.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(ImplementationStatus::class),
                SelectFilter::make('effectiveness')
                    ->options(Effectiveness::class)
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('auditItems', function ($q) use ($data) {
                            $q->where('effectiveness', $data['value']);
                        });
                    }),
                Tables\Filters\SelectFilter::make('implementation_owner_id')
                    ->label('Owner')
                    ->options(User::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                //                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Details')
                    ->schema([
                        TextEntry::make('code')
                            ->columnSpan(2)
                            ->getStateUsing(fn ($record) => "$record->code - $record->title")
                            ->label('Title'),
                        TextEntry::make('effectiveness')
                            ->getStateUsing(fn ($record) => $record->getEffectiveness())
                            ->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('details')
                            ->columnSpanFull()
                            ->html(),
                        TextEntry::make('notes')
                            ->columnSpanFull()
                            ->html(),
                    ])
                    ->columns(4),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ControlsRelationManager::class,
            RelationManagers\AuditItemRelationManager::class,
            RelationManagers\RisksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImplementations::route('/'),
            'create' => Pages\CreateImplementation::route('/create'),
            'view' => Pages\ViewImplementations::route('/{record}'),
            'edit' => Pages\EditImplementation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * @param  Implementation  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return "$record->code - $record->title";
    }

    /**
     * @param  Implementation  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ImplementationResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Implementation  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Implementation' => $record->title,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'details', 'notes', 'code'];
    }

    public static function getForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. ACME-123')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Give the implementation a unique ID or Code.'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->enum(ImplementationStatus::class)
                    ->options(ImplementationStatus::class)
                    ->default(ImplementationStatus::UNKNOWN)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select an implementation status. This will also be assessed in audits.')
                    ->native(false),
                Forms\Components\TextInput::make('title')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Quarterly Access Reviews')
                    ->hint('Enter the title of the implementation.')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'This should be a detailed description of this implementation in sufficient detail to both implement and test.'),
                Forms\Components\RichEditor::make('details')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->label('Implementation Details')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'This should be a detailed description of this implementation in sufficient detail to both implement and test.'),
                Forms\Components\RichEditor::make('notes')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles'
                    ])
                    ->label('Internal Notes')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'These notes are for internal use only and will not be shared with auditors.')
                    ->maxLength(4096),
            ]);
    }

    public static function getTable(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('details')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('effectiveness')
                    ->getStateUsing(function ($record) {
                        return $record->getEffectiveness();
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('last_assessed')
                    ->label('Last Audit')
                    ->getStateUsing(fn ($record) => $record->getEffectivenessDate() ? $record->getEffectivenessDate() : 'Not yet audited')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('implementationOwner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(ImplementationStatus::class),
                SelectFilter::make('effectiveness')
                    ->options(Effectiveness::class)
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return $query->whereHas('auditItems', function ($q) use ($data) {
                            $q->where('effectiveness', $data['value']);
                        });
                    }),
                Tables\Filters\SelectFilter::make('implementation_owner_id')
                    ->label('Owner')
                    ->options(User::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }
}
