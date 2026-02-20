<?php

namespace App\Filament\Resources;

use App\Enums\MitigationType;
use App\Enums\RiskLevel;
use App\Enums\RiskStatus;
use App\Filament\Columns\TaxonomyColumn;
use App\Filament\Concerns\HasTaxonomyFields;
use App\Filament\Exports\RiskExporter;
use App\Filament\Filters\TaxonomySelectFilter;
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
use Illuminate\Database\Eloquent\Builder;
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
                            ->options(RiskLevel::options())
                            ->grouped()
                            ->required(),
                        ToggleButtons::make('inherent_impact')
                            ->label('Impact')
                            ->options(RiskLevel::options())
                            ->grouped()
                            ->required(),
                    ]),
                Section::make('residual')
                    ->columnSpan(1)
                    ->heading('Residual Risk Scoring')
                    ->schema([
                        ToggleButtons::make('residual_likelihood')
                            ->label('Likelihood')
                            ->options(RiskLevel::options())
                            ->grouped()
                            ->required(),
                        ToggleButtons::make('residual_impact')
                            ->label('Impact')
                            ->options(RiskLevel::options())
                            ->grouped()
                            ->required(),
                    ]),

                Select::make('implementations')
                    ->label('Related Implementations')
                    ->helperText('What are we doing to mitigate this risk?')
                    ->relationship(name: 'implementations')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "({$record->code}) {$record->title}")
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
                    ->wrap()
                    ->limit(100),
                TextColumn::make('description')
                    ->wrap()
                    ->limit(250),
                TextColumn::make('inherent_risk')
                    ->label('Inherent Risk')
                    ->getStateUsing(fn (Risk $record) => RiskLevel::formatRisk($record->inherent_likelihood, $record->inherent_impact))
                    ->color(fn (Risk $record) => RiskLevel::getColor($record->inherent_likelihood, $record->inherent_impact))
                    ->badge()
                    ->sortable(),
                TextColumn::make('residual_risk')
                    ->label('Residual Risk')
                    ->getStateUsing(fn (Risk $record) => RiskLevel::formatRisk($record->residual_likelihood, $record->residual_impact))
                    ->color(fn (Risk $record) => RiskLevel::getColor($record->residual_likelihood, $record->residual_impact))
                    ->badge()
                    ->sortable(),
                TaxonomyColumn::make('department'),
                TaxonomyColumn::make('scope'),
                TextColumn::make('mitigation_status')
                    ->label('Mitigation')
                    ->getStateUsing(function (Risk $record) {
                        $strategies = $record->mitigations()->pluck('strategy')->unique()->map(fn ($s) => $s->value)->values()->toArray();

                        return empty($strategies) ? ['None'] : $strategies;
                    })
                    ->badge()
                    ->searchable(false)
                    ->color(fn (string $state) => MitigationType::tryFrom($state)?->getColor() ?? 'gray')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->withCount('mitigations')
                            ->orderBy('mitigations_count', $direction);
                    }),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->getStateUsing(fn (Risk $record) => $record->is_active ? 'Active' : 'Inactive')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('inherent_likelihood')
                    ->label('Inherent Likelihood')
                    ->options(RiskLevel::options()),
                SelectFilter::make('inherent_impact')
                    ->label('Inherent Impact')
                    ->options(RiskLevel::options()),
                SelectFilter::make('residual_likelihood')
                    ->label('Residual Likelihood')
                    ->options(RiskLevel::options()),
                SelectFilter::make('residual_impact')
                    ->label('Residual Impact')
                    ->options(RiskLevel::options()),
                TaxonomySelectFilter::make('department'),
                TaxonomySelectFilter::make('scope'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['taxonomies']);
    }
}
