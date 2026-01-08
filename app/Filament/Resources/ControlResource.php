<?php

namespace App\Filament\Resources;

use AmidEsfahani\FilamentTinyEditor\TinyEditor;
use App\Enums\Applicability;
use App\Enums\ControlCategory;
use App\Enums\ControlEnforcementCategory;
use App\Enums\ControlType;
use App\Enums\Effectiveness;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Resources\ControlResource\Pages;
use App\Filament\Resources\ControlResource\RelationManagers;
use App\Models\Control;
use App\Models\Standard;
use App\Models\User;
use Exception;
use Filament\Forms;
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
use Illuminate\Validation\Rules\Unique;

class ControlResource extends Resource
{
    use HasTaxonomyFields;

    protected static ?string $model = Control::class;

    protected static ?string $navigationIcon = 'heroicon-o-stop-circle';

    protected static ?string $navigationLabel = null;

    protected static ?string $navigationGroup = null;

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

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.code.tooltip'))
                    ->maxLength(255)
                    ->unique(
                        table: Control::class,
                        column: 'code',
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Forms\Get $get) {
                            // Control codes must be unique within their standard,
                            // but different standards can have the same control code
                            return $rule->where('standard_id', $get('standard_id'));
                        }
                    )
                    ->live()
                    ->afterStateUpdated(function (Forms\Contracts\HasForms $livewire, Forms\Components\TextInput $component) {
                        $livewire->validateOnly($component->getStatePath());
                    }),
                Forms\Components\Select::make('standard_id')
                    ->label(__('control.form.standard.label'))
                    ->searchable()
                    ->options(Standard::pluck('name', 'id')->toArray())
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.standard.tooltip'))
                    ->required(),
                Forms\Components\Select::make('enforcement')
                    ->options(ControlEnforcementCategory::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.enforcement.tooltip'))
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label(__('control.form.type.label'))
                    ->options(ControlType::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.type.tooltip'))
                    ->required(),
                Forms\Components\Select::make('category')
                    ->label(__('control.form.category.label'))
                    ->options(ControlCategory::class)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.category.tooltip'))
                    ->required(),
                Forms\Components\Select::make('control_owner_id')
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
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(1024)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.title.tooltip'))
                    ->maxLength(1024),
                TinyEditor::make('description')
                    ->required()
                    ->maxLength(65535)
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.description.tooltip'))
                    ->extraInputAttributes(['class' => 'filament-forms-rich-editor-unfiltered'])
                    ->columnSpanFull(),
                TinyEditor::make('discussion')
                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: __('control.form.discussion.tooltip'))
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TinyEditor::make('test')
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
            ->description(new class implements \Illuminate\Contracts\Support\Htmlable
            {
                public function toHtml()
                {
                    return "<div class='fi-section-content p-6'>".
                        __('control.table.description').
                        '</div>';
                }
            })
            ->emptyStateHeading(__('control.table.empty_state.heading'))
            ->emptyStateDescription(new HtmlString(__('control.table.empty_state.description', [
                'url' => route('filament.app.resources.controls.index'),
            ])))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label(__('control.table.columns.code'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('control.table.columns.title'))
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('standard.name')
                    ->label(__('control.table.columns.standard'))
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('control.table.columns.type'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label(__('control.table.columns.category'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('enforcement')
                    ->label(__('control.table.columns.enforcement'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('LatestAuditEffectiveness')
                    ->label(__('control.table.columns.effectiveness'))
                    ->badge()
                    ->sortable()
                    ->default(function (Control $record) {
                        return $record->getEffectiveness();
                    }),
                Tables\Columns\TextColumn::make('applicability')
                    ->label(__('control.table.columns.applicability'))
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('LatestAuditDate')
                    ->label(__('control.table.columns.assessed'))
                    ->sortable()
                    ->default(function (Control $record) {
                        return $record->getEffectivenessDate();
                    }),
                Tables\Columns\TextColumn::make('controlOwner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('taxonomy_department')
                    ->label('Department')
                    ->getStateUsing(function (Control $record) {
                        return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $departmentParent = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('slug', 'department')->whereNull('parent_id')->first();
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
                Tables\Columns\TextColumn::make('taxonomy_scope')
                    ->label('Scope')
                    ->getStateUsing(function (Control $record) {
                        return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $scopeParent = \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('slug', 'scope')->whereNull('parent_id')->first();
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('control.table.columns.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('control.table.columns.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('standard_id')
                    ->options(Standard::pluck('name', 'id')->toArray())
                    ->multiple()
                    ->label(__('control.table.filters.standard')),
                Tables\Filters\SelectFilter::make('effectiveness')
                    ->options(Effectiveness::class)
                    ->label(__('control.table.filters.effectiveness')),
                Tables\Filters\SelectFilter::make('type')
                    ->options(ControlType::class)
                    ->label(__('control.table.filters.type')),
                Tables\Filters\SelectFilter::make('category')
                    ->options(ControlCategory::class)
                    ->label(__('control.table.filters.category')),
                Tables\Filters\SelectFilter::make('enforcement')
                    ->options(ControlEnforcementCategory::class)
                    ->label(__('control.table.filters.enforcement')),
                Tables\Filters\SelectFilter::make('applicability')
                    ->options(Applicability::class)
                    ->label(__('control.table.filters.applicability')),
                Tables\Filters\SelectFilter::make('control_owner_id')
                    ->label('Owner')
                    ->options(User::pluck('name', 'id')->toArray()),
                Tables\Filters\SelectFilter::make('department')
                    ->label('Department')
                    ->options(function () {
                        $taxonomy = self::getParentTaxonomy('department');

                        if (! $taxonomy) {
                            return [];
                        }

                        return \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $taxonomy->id)
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
                Tables\Filters\SelectFilter::make('scope')
                    ->label('Scope')
                    ->options(function () {
                        $taxonomy = self::getParentTaxonomy('scope');

                        if (! $taxonomy) {
                            return [];
                        }

                        return \Aliziodev\LaravelTaxonomy\Models\Taxonomy::where('parent_id', $taxonomy->id)
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ImplementationRelationManager::class,
            RelationManagers\AuditItemRelationManager::class,
            RelationManagers\PoliciesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListControls::route('/'),
            'create' => Pages\CreateControl::route('/create'),
            'view' => Pages\ViewControl::route('/{record}'),
            'edit' => Pages\EditControl::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make(__('control.infolist.section_title'))
                    ->columns(3)
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
                    ]),
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
