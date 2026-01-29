<?php

namespace App\Filament\Resources;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use App\Filament\Columns\TaxonomyColumn;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Exports\ImplementationExporter;
use App\Filament\Filters\TaxonomySelectFilter;
use App\Filament\Resources\ImplementationResource\Pages\CreateImplementation;
use App\Filament\Resources\ImplementationResource\Pages\EditImplementation;
use App\Filament\Resources\ImplementationResource\Pages\ListImplementations;
use App\Filament\Resources\ImplementationResource\Pages\ViewImplementations;
use App\Filament\Resources\ImplementationResource\RelationManagers\ApplicationsRelationManager;
use App\Filament\Resources\ImplementationResource\RelationManagers\AssetsRelationManager;
use App\Filament\Resources\ImplementationResource\RelationManagers\AuditItemRelationManager;
use App\Filament\Resources\ImplementationResource\RelationManagers\ControlsRelationManager;
use App\Filament\Resources\ImplementationResource\RelationManagers\PoliciesRelationManager;
use App\Filament\Resources\ImplementationResource\RelationManagers\RisksRelationManager;
use App\Filament\Resources\ImplementationResource\RelationManagers\VendorsRelationManager;
use App\Models\Application;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\User;
use App\Models\Vendor;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Component;

class ImplementationResource extends Resource
{
    use HasTaxonomyFields;

    protected static ?string $model = Implementation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('implementation.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('implementation.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('code')
                    ->maxLength(255)
                    ->required()
                    ->unique(Implementation::class, 'code', ignoreRecord: true)
                    ->live()
                    ->afterStateUpdated(function (Component $livewire, TextInput $component) {
                        $livewire->validateOnly($component->getStatePath());
                    })
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a unique code for this implementation. This code will be used to identify this implementation in the system.'),
                Select::make('status')
                    ->required()
                    ->label('Implementation Status')
                    ->enum(ImplementationStatus::class)
                    ->options(ImplementationStatus::class)
                    ->default(ImplementationStatus::UNKNOWN)
                    ->native(false)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select the the best implementation level for this implementation. This can be assessed and changed later.'),

                Select::make('controls')
                    ->label('Related Controls')
                    ->relationship('controls', 'code')
                    ->options(
                        Control::all()->mapWithKeys(function ($control) {
                            return [$control->id => "($control->code) - $control->title"];
                        })->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->default(function (Select $component) {
                        $livewire = $component->getLivewire();
                        if ($livewire instanceof RelationManager) {
                            return [$livewire->getOwnerRecord()->getKey()];
                        }

                        return null;
                    })
                    ->placeholder('Select related controls')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: "All implementations should relate to a control. If you don't have a relevant control in place, consider creating a new one first."),
                Select::make('applications')
                    ->label('Related Applications')
                    ->relationship('applications', 'name')
                    ->options(
                        Application::all()->mapWithKeys(function ($application) {
                            return [$application->id => $application->name];
                        })->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->placeholder('Select related applications')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select applications that support or relate to this implementation.'),
                Select::make('vendors')
                    ->label('Related Vendors')
                    ->relationship('vendors', 'name')
                    ->options(
                        Vendor::all()->mapWithKeys(function ($vendor) {
                            return [$vendor->id => $vendor->name];
                        })->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->placeholder('Select related vendors')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select vendors that support or relate to this implementation.'),
                TextInput::make('title')
                    ->maxLength(255)
                    ->required()
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a title for this implementation.'),
                Select::make('implementation_owner_id')
                    ->label('Owner')
                    ->options(User::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->nullable()
                    ->columnSpan(1),
                self::taxonomySelect('Department', 'department')
                    ->nullable()
                    ->columnSpan(1),
                self::taxonomySelect('Scope', 'scope')
                    ->nullable()
                    ->columnSpan(1),
                RichEditor::make('details')
                    ->required()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles',
                    ])
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter a description for this implementation. This be an in-depth description of how this implementation is put in place.'),

                RichEditor::make('test_procedure')
                    ->label('Test Procedure')
                    ->maxLength(65535)
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles',
                    ])
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enter the procedure for testing this implementation during audits.'),

                RichEditor::make('notes')
                    ->maxLength(65535)
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles',
                    ])
                    ->columnSpanFull()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Any additional internal notes. This is never visible to an auditor.'),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->emptyStateHeading(__('implementation.table.empty_state.heading'))
            ->emptyStateDescription(__('implementation.table.empty_state.description'))
            ->columns([
                TextColumn::make('code')
                    ->label(__('implementation.table.columns.code'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('implementation.table.columns.title'))
                    ->toggleable()
                    ->sortable()
                    ->wrap()
                    ->searchable(),
                TextColumn::make('effectiveness')
                    ->label(__('implementation.table.columns.effectiveness'))
                    ->getStateUsing(fn ($record) => $record->getEffectiveness())
                    ->sortable()
                    ->badge(),
                TextColumn::make('last_assessed')
                    ->label(__('implementation.table.columns.last_assessed'))
                    ->getStateUsing(fn ($record) => $record->getEffectivenessDate() ? $record->getEffectivenessDate() : 'Not yet audited')
                    ->sortable()
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('implementation.table.columns.status'))
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('implementationOwner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TaxonomyColumn::make('department')
                    ->toggleable(),
                TaxonomyColumn::make('scope')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('implementation.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
                SelectFilter::make('implementation_owner_id')
                    ->label('Owner')
                    ->options(User::pluck('name', 'id')->toArray()),
                TaxonomySelectFilter::make('department'),
                TaxonomySelectFilter::make('scope'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ImplementationExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->recordActions([
                ViewAction::make(),
                //                Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ImplementationExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('code')
                            ->columnSpan(2)
                            ->getStateUsing(fn ($record) => "$record->code - $record->title")
                            ->label('Title'),
                        TextEntry::make('effectiveness')
                            ->getStateUsing(fn ($record) => $record->getEffectiveness())
                            ->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('taxonomies')
                            ->label('Department')
                            ->getStateUsing(function (Implementation $record) {
                                return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('taxonomies')
                            ->label('Scope')
                            ->getStateUsing(function (Implementation $record) {
                                return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('details')
                            ->columnSpanFull()
                            ->html(),
                        TextEntry::make('test_procedure')
                            ->label('Test Procedure')
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
            ControlsRelationManager::class,
            AuditItemRelationManager::class,
            RisksRelationManager::class,
            AssetsRelationManager::class,
            ApplicationsRelationManager::class,
            VendorsRelationManager::class,
            PoliciesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImplementations::route('/'),
            'create' => CreateImplementation::route('/create'),
            'view' => ViewImplementations::route('/{record}'),
            'edit' => EditImplementation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['taxonomies', 'latestCompletedAudit', 'implementationOwner']);
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

    public static function getForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. ACME-123')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Give the implementation a unique ID or Code.'),
                Select::make('status')
                    ->required()
                    ->enum(ImplementationStatus::class)
                    ->options(ImplementationStatus::class)
                    ->default(ImplementationStatus::UNKNOWN)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Select an implementation status. This will also be assessed in audits.')
                    ->native(false),
                TextInput::make('title')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Quarterly Access Reviews')
                    ->hint('Enter the title of the implementation.')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'This should be a detailed description of this implementation in sufficient detail to both implement and test.'),
                RichEditor::make('details')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles',
                    ])
                    ->label('Implementation Details')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'This should be a detailed description of this implementation in sufficient detail to both implement and test.'),
                RichEditor::make('notes')
                    ->columnSpanFull()
                    ->disableToolbarButtons([
                        'image',
                        'attachFiles',
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
                TextColumn::make('code')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('effectiveness')
                    ->getStateUsing(function ($record) {
                        return $record->getEffectiveness();
                    })
                    ->badge(),
                TextColumn::make('last_assessed')
                    ->label('Last Audit')
                    ->getStateUsing(fn ($record) => $record->getEffectivenessDate() ? $record->getEffectivenessDate() : 'Not yet audited')
                    ->badge(),
                TextColumn::make('status')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('implementationOwner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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
                SelectFilter::make('implementation_owner_id')
                    ->label('Owner')
                    ->options(User::pluck('name', 'id')->toArray()),
                TaxonomySelectFilter::make('department'),
                TaxonomySelectFilter::make('scope'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
