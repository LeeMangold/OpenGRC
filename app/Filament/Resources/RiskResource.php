<?php

namespace App\Filament\Resources;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Enums\RiskStatus;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Exports\RiskExporter;
use App\Filament\Resources\RiskResource\Pages\CreateRisk;
use App\Filament\Resources\RiskResource\Pages\ListRiskActivities;
use App\Filament\Resources\RiskResource\Pages\ListRisks;
use App\Filament\Resources\RiskResource\Pages\ViewRisk;
use App\Filament\Resources\RiskResource\RelationManagers\ImplementationsRelationManager;
use App\Filament\Resources\RiskResource\RelationManagers\MitigationsRelationManager;
use App\Filament\Resources\RiskResource\RelationManagers\PoliciesRelationManager;
use App\Models\Risk;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class RiskResource extends Resource
{
    use HasTaxonomyFields;

    protected static ?string $model = Risk::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('risk-management.navigation_label');
    }

    public static function form(Schema $schema): Schema
    {

        return $schema
            ->columns()
            ->components([
                TextInput::make('code')
                    ->label('Code')
                    ->unique('risks', 'code', ignoreRecord: true)
                    ->columnSpanFull()
                    ->required(),
                TextInput::make('name')
                    ->label('Name')
                    ->columnSpanFull()
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Description'),
                Section::make('inherent')
                    ->columnSpan(1)
                    ->heading('Inherent Risk Scoring')
                    ->schema([
                        ToggleButtons::make('inherent_likelihood')
                            ->label('Likelihood')
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                        ToggleButtons::make('inherent_impact')
                            ->label('Impact')
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                    ]),
                Section::make('residual')
                    ->columnSpan(1)
                    ->heading('Residual Risk Scoring')
                    ->schema([
                        ToggleButtons::make('residual_likelihood')
                            ->label('Likelihood')
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                        ToggleButtons::make('residual_impact')
                            ->label('Impact')
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                    ]),

                Select::make('implementations')
                    ->label('Related Implementations')
                    ->helperText('What are we doing to mitigate this risk?')
                    ->relationship(name: 'implementations', titleAttribute: 'title')
                    ->searchable(['title', 'code'])
                    ->multiple(),

                Select::make('status')
                    ->label('Status')
                    ->enum(RiskStatus::class)
                    ->options(RiskStatus::class)
                    ->required(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive risks are excluded from reports and dashboards'),
                self::taxonomySelect('Department', 'department')
                    ->nullable()
                    ->columnSpan(1),
                self::taxonomySelect('Scope', 'scope')
                    ->nullable()
                    ->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultSort('residual_risk', 'desc')
            ->emptyStateHeading('No Risks Identified Yet')
            ->emptyStateDescription('Add and analyse your first risk by clicking the "Track New Risk" button above.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->wrap()
                    ->formatStateUsing(function ($state) {
                        // Insert a zero-width space every 30 characters in long words
                        return preg_replace_callback('/\S{30,}/', function ($matches) {
                            return wordwrap($matches[0], 30, "\u{200B}", true);
                        }, $state);
                    })
                    ->limit(100)
                    ->sortable(),
                TextColumn::make('description')
                    ->searchable()
                    ->wrap()
                    ->limit(250)
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        // Insert a zero-width space every 50 characters in long words
                        return preg_replace_callback('/\S{50,}/', function ($matches) {
                            return wordwrap($matches[0], 50, "\u{200B}", true);
                        }, $state);
                    }),
                TextColumn::make('inherent_risk')
                    ->label('Inherent Risk')
                    ->sortable()
                    ->color(function (Risk $record) {
                        return self::getRiskColor($record->inherent_likelihood, $record->inherent_impact);
                    })
                    ->badge(),
                TextColumn::make('residual_risk')
                    ->sortable()
                    ->badge()
                    ->color(function (Risk $record) {
                        return self::getRiskColor($record->residual_likelihood, $record->residual_impact);
                    }),
                TextColumn::make('taxonomy_department')
                    ->label('Department')
                    ->getStateUsing(function (Risk $record) {
                        return self::getTaxonomyTerm($record, 'department')?->name ?? 'Not assigned';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $departmentParent = Taxonomy::where('slug', 'department')->whereNull('parent_id')->first();
                        if (! $departmentParent) {
                            return;
                        }

                        $query->leftJoin('taxonomables as dept_taxonomables', function ($join) {
                            $join->on('risks.id', '=', 'dept_taxonomables.taxonomable_id')
                                ->where('dept_taxonomables.taxonomable_type', '=', 'App\\Models\\Risk');
                        })
                            ->leftJoin('taxonomies as dept_taxonomies', function ($join) use ($departmentParent) {
                                $join->on('dept_taxonomables.taxonomy_id', '=', 'dept_taxonomies.id')
                                    ->where('dept_taxonomies.parent_id', '=', $departmentParent->id);
                            })
                            ->orderBy('dept_taxonomies.name', $direction)
                            ->select('risks.*');
                    })
                    ->toggleable(),
                TextColumn::make('taxonomy_scope')
                    ->label('Scope')
                    ->getStateUsing(function (Risk $record) {
                        return self::getTaxonomyTerm($record, 'scope')?->name ?? 'Not assigned';
                    })
                    ->sortable(query: function ($query, string $direction): void {
                        $scopeParent = Taxonomy::where('slug', 'scope')->whereNull('parent_id')->first();
                        if (! $scopeParent) {
                            return;
                        }

                        $query->leftJoin('taxonomables as scope_taxonomables', function ($join) {
                            $join->on('risks.id', '=', 'scope_taxonomables.taxonomable_id')
                                ->where('scope_taxonomables.taxonomable_type', '=', 'App\\Models\\Risk');
                        })
                            ->leftJoin('taxonomies as scope_taxonomies', function ($join) use ($scopeParent) {
                                $join->on('scope_taxonomables.taxonomy_id', '=', 'scope_taxonomies.id')
                                    ->where('scope_taxonomies.parent_id', '=', $scopeParent->id);
                            })
                            ->orderBy('scope_taxonomies.name', $direction)
                            ->select('risks.*');
                    })
                    ->toggleable(),
                TextColumn::make('mitigation_status')
                    ->label('Mitigation')
                    ->getStateUsing(fn (Risk $record) => $record->mitigations()->exists() ? 'Applied' : 'None')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Applied' ? 'success' : 'gray')
                    ->toggleable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->getStateUsing(fn (Risk $record) => $record->is_active ? 'Active' : 'Inactive')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('inherent_likelihood')
                    ->label('Inherent Likelihood')
                    ->options([
                        '1' => 'Very Low',
                        '2' => 'Low',
                        '3' => 'Moderate',
                        '4' => 'High',
                        '5' => 'Very High',
                    ]),
                SelectFilter::make('inherent_impact')
                    ->label('Inherent Impact')
                    ->options([
                        '1' => 'Very Low',
                        '2' => 'Low',
                        '3' => 'Moderate',
                        '4' => 'High',
                        '5' => 'Very High',
                    ]),
                SelectFilter::make('residual_likelihood')
                    ->label('Residual Likelihood')
                    ->options([
                        '1' => 'Very Low',
                        '2' => 'Low',
                        '3' => 'Moderate',
                        '4' => 'High',
                        '5' => 'Very High',
                    ]),
                SelectFilter::make('residual_impact')
                    ->label('Residual Impact')
                    ->options([
                        '1' => 'Very Low',
                        '2' => 'Low',
                        '3' => 'Moderate',
                        '4' => 'High',
                        '5' => 'Very High',
                    ]),
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
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ])
                    ->default('1'),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(RiskExporter::class)
                    ->icon('heroicon-o-arrow-down-tray'),
                Action::make('reset_filters')
                    ->label('Reset Filters')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->alpineClickHandler("\$dispatch('reset-risk-filters')")
                    ->visible(fn ($livewire) => $livewire->hasActiveRiskFilters ?? request()->has('tableFilters')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->hidden(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(RiskExporter::class)
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray'),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            'implementations' => ImplementationsRelationManager::class,
            'policies' => PoliciesRelationManager::class,
            'mitigations' => MitigationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRisks::route('/'),
            'create' => CreateRisk::route('/create'),
            'view' => ViewRisk::route('/{record}'),
            'activities' => ListRiskActivities::route('/{record}/activities'),
        ];
    }

    /**
     * @param  Risk  $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return "$record->name";
    }

    /**
     * @param  Risk  $record
     */
    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return RiskResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @param  Risk  $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Risk' => $record->id,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description'];
    }

    // Mentioning the following classes to prevent them from being removed.
    // bg-grcblue-200 bg-red-200 bg-orange-200 bg-yellow-200 bg-green-200
    // bg-grcblue-500 bg-red-500 bg-orange-500 bg-yellow-500 bg-green-500

    public static function getRiskColor(int $likelihood, int $impact, int $weight = 200): string
    {
        $average = round(($likelihood + $impact) / 2);

        if ($average >= 5) {
            return "bg-red-$weight"; // High risk
        } elseif ($average >= 4) {
            return "bg-orange-$weight"; // Moderate-High risk
        } elseif ($average >= 3) {
            return "bg-yellow-$weight"; // Moderate risk
        } elseif ($average >= 2) {
            return "bg-grcblue-$weight"; // Moderate risk
        } else {
            return "bg-green-$weight"; // Low risk
        }
    }
}
