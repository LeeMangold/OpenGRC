<?php

namespace App\Filament\Resources;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Enums\Applicability;
use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\Effectiveness;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Exports\ControlExporter;
use App\Filament\Resources\ControlResource\Pages\CreateControl;
use App\Filament\Resources\ControlResource\Pages\EditControl;
use App\Filament\Resources\ControlResource\Pages\ListControls;
use App\Filament\Resources\ControlResource\Pages\ViewControl;
use App\Filament\Resources\ControlResource\RelationManagers\AuditItemRelationManager;
use App\Filament\Resources\ControlResource\RelationManagers\ImplementationRelationManager;
use App\Filament\Resources\ControlResource\RelationManagers\PoliciesRelationManager;
use App\Models\Control;
use App\Models\Standard;
use App\Models\User;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class ControlResource extends Resource
{
    use HasTaxonomyFields;

    protected static ?string $model = Control::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-stop-circle';

    protected static ?string $navigationLabel = null;

    protected static string|\UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('control.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('control.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return __('control.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('control.model.plural_label');
    }

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                TextInput::make('code')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.code.tooltip'))
                    ->maxLength(255)
                    ->unique(Control::class, 'code', ignoreRecord: true)
                    ->live()
                    ->afterStateUpdated(function (Component $livewire, TextInput $component) {
                        $livewire->validateOnly($component->getStatePath());
                    }),
                Select::make('standard_id')
                    ->label(__('control.form.standard.label'))
                    ->searchable()
                    ->options(Standard::pluck('name', 'id')->toArray())
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.standard.tooltip'))
                    ->default(function (Get $get, Select $component) {
                        $livewire = $component->getLivewire();
                        if ($livewire instanceof RelationManager) {
                            return $livewire->getOwnerRecord()->getKey();
                        }

                        return null;
                    })
                    ->disabled(function (Select $component) {
                        $livewire = $component->getLivewire();

                        return $livewire instanceof RelationManager;
                    })
                    ->dehydrated()
                    ->required(),
                Select::make('enforcement')
                    ->options(ControlEnforcementCategory::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.enforcement.tooltip'))
                    ->required(),
                Select::make('type')
                    ->label(__('control.form.type.label'))
                    ->options(ControlType::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.type.tooltip'))
                    ->required(),
                Select::make('category')
                    ->label(__('control.form.category.label'))
                    ->options(ControlCategory::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.category.tooltip'))
                    ->required(),
                Select::make('control_owner_id')
                    ->label('Control Owner')
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
                TextInput::make('title')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(1024)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.title.tooltip'))
                    ->maxLength(1024),
                RichEditor::make('description')
                    ->required()
                    ->maxLength(65535)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.description.tooltip'))
                    ->columnSpanFull(),
                RichEditor::make('discussion')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.discussion.tooltip'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                RichEditor::make('test')
                    ->label(__('control.form.test.label'))
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.test.tooltip'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->emptyStateHeading(__('control.table.empty_state.heading'))
            ->emptyStateDescription(new HtmlString(__('control.table.empty_state.description', [
                'url' => route('filament.app.resources.controls.index'),
            ])))
            ->columns([
                TextColumn::make('code')
                    ->label(__('control.table.columns.code'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('control.table.columns.title'))
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('standard.name')
                    ->label(__('control.table.columns.standard'))
                    ->wrap()
                    ->sortable(),
                TextColumn::make('type')
                    ->label(__('control.table.columns.type'))
                    ->sortable(),
                TextColumn::make('category')
                    ->label(__('control.table.columns.category'))
                    ->sortable(),
                TextColumn::make('enforcement')
                    ->label(__('control.table.columns.enforcement'))
                    ->sortable(),
                TextColumn::make('LatestAuditEffectiveness')
                    ->label(__('control.table.columns.effectiveness'))
                    ->badge()
                    ->sortable()
                    ->default(function (Control $record) {
                        return $record->getEffectiveness();
                    }),
                TextColumn::make('applicability')
                    ->label(__('control.table.columns.applicability'))
                    ->sortable()
                    ->badge(),
                TextColumn::make('LatestAuditDate')
                    ->label(__('control.table.columns.assessed'))
                    ->sortable()
                    ->default(function (Control $record) {
                        return $record->getEffectivenessDate();
                    }),
                TextColumn::make('controlOwner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('taxonomy_department')
                    ->label('Department')
                    ->getStateUsing(function (Control $record) {
                        return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $departmentParent = Taxonomy::where('slug', 'department')->whereNull('parent_id')->first();
                        if (! $departmentParent) {
                            return;
                        }

                        $query->leftJoin('taxonomables as dept_taxonomables', function ($join) {
                            $join->on('controls.id', '=', 'dept_taxonomables.taxonomable_id')
                                ->where('dept_taxonomables.taxonomable_type', '=', 'App\\Models\\Control');
                        })
                            ->leftJoin('taxonomies as dept_taxonomies', function ($join) use ($departmentParent) {
                                $join->on('dept_taxonomables.taxonomy_id', '=', 'dept_taxonomies.id')
                                    ->where('dept_taxonomies.parent_id', '=', $departmentParent->id);
                            })
                            ->orderBy('dept_taxonomies.name', $direction)
                            ->select('controls.*');
                    })
                    ->toggleable(),
                TextColumn::make('taxonomy_scope')
                    ->label('Scope')
                    ->getStateUsing(function (Control $record) {
                        return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $scopeParent = Taxonomy::where('slug', 'scope')->whereNull('parent_id')->first();
                        if (! $scopeParent) {
                            return;
                        }

                        $query->leftJoin('taxonomables as scope_taxonomables', function ($join) {
                            $join->on('controls.id', '=', 'scope_taxonomables.taxonomable_id')
                                ->where('scope_taxonomables.taxonomable_type', '=', 'App\\Models\\Control');
                        })
                            ->leftJoin('taxonomies as scope_taxonomies', function ($join) use ($scopeParent) {
                                $join->on('scope_taxonomables.taxonomy_id', '=', 'scope_taxonomies.id')
                                    ->where('scope_taxonomies.parent_id', '=', $scopeParent->id);
                            })
                            ->orderBy('scope_taxonomies.name', $direction)
                            ->select('controls.*');
                    })
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('control.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('control.table.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('standard_id')
                    ->options(Standard::pluck('name', 'id')->toArray())
                    ->multiple()
                    ->label(__('control.table.filters.standard')),
                SelectFilter::make('effectiveness')
                    ->options(Effectiveness::class)
                    ->label(__('control.table.filters.effectiveness')),
                SelectFilter::make('type')
                    ->options(ControlType::class)
                    ->label(__('control.table.filters.type')),
                SelectFilter::make('category')
                    ->options(ControlCategory::class)
                    ->label(__('control.table.filters.category')),
                SelectFilter::make('enforcement')
                    ->options(ControlEnforcementCategory::class)
                    ->label(__('control.table.filters.enforcement')),
                SelectFilter::make('applicability')
                    ->options(Applicability::class)
                    ->label(__('control.table.filters.applicability')),
                SelectFilter::make('control_owner_id')
                    ->label('Owner')
                    ->options(User::pluck('name', 'id')->toArray()),
                SelectFilter::make('department')
                    ->label('Department')
                    ->options(function () {
                        $taxonomy = self::getParentTaxonomy('department');

                        if (! $taxonomy) {
                            return [];
                        }

                        return Taxonomy::where('parent_id', $taxonomy->id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return;
                        }

                        $query->whereHas('taxonomies', function ($query) use ($data) {
                            $query->where('taxonomy_id', $data['value']);
                        });
                    }),
                SelectFilter::make('scope')
                    ->label('Scope')
                    ->options(function () {
                        $taxonomy = self::getParentTaxonomy('scope');

                        if (! $taxonomy) {
                            return [];
                        }

                        return Taxonomy::where('parent_id', $taxonomy->id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->query(function ($query, array $data) {
                        if (! $data['value']) {
                            return;
                        }

                        $query->whereHas('taxonomies', function ($query) use ($data) {
                            $query->where('taxonomy_id', $data['value']);
                        });
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(ControlExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(ControlExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ImplementationRelationManager::class,
            AuditItemRelationManager::class,
            PoliciesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListControls::route('/'),
            'create' => CreateControl::route('/create'),
            'view' => ViewControl::route('/{record}'),
            'edit' => EditControl::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('control.infolist.section_title'))
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('code'),
                        TextEntry::make('effectiveness')
                            ->default(function (Control $record) {
                                return $record->getEffectiveness();
                            }),
                        TextEntry::make('type')->badge(),
                        TextEntry::make('category')->badge(),
                        TextEntry::make('enforcement')->badge(),
                        TextEntry::make('lastAuditDate')
                            ->default(function (Control $record) {
                                return $record->getEffectivenessDate();
                            }),
                        TextEntry::make('taxonomies')
                            ->label('Department')
                            ->formatStateUsing(function (Control $record) {
                                return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('taxonomies')
                            ->label('Scope')
                            ->formatStateUsing(function (Control $record) {
                                return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                            }),
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'control-description-text'])
                            ->html(),
                        TextEntry::make('discussion')
                            ->columnSpanFull()
                            ->hidden(fn (Control $record) => ! $record->discussion)
                            ->html(),
                        TextEntry::make('test')
                            ->label(__('control.infolist.test_plan'))
                            ->columnSpanFull()
                            ->hidden(fn (Control $record) => ! $record->discussion)
                            ->html(),
                    ])->columns(4),
            ]);
    }

    /**
     * @param  Control  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return "$record->code - $record->title";
    }

    /**
     * @param  Control  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return ControlResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Control  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Control' => $record->title,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'discussion', 'code'];
    }
}
