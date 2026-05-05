<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccTowerLightingInspectionResource\Pages\ManageFccTowerLightingInspections;
use App\Models\FccTowerLightingInspection;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FccTowerLightingInspectionResource extends Resource
{
    protected static ?string $model = FccTowerLightingInspection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = 'Tower Inspections';

    protected static ?string $modelLabel = 'Tower Lighting Inspection';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('asr_registration_id')->relationship('asr', 'asr_number')->searchable()->label('ASR'),
            Select::make('facility_id')->relationship('facility', 'name')->searchable()->label('Facility'),
            DatePicker::make('inspection_date')->required(),
            TextInput::make('inspector_name'),
            Select::make('result')->required()->options([
                'operational' => 'Fully Operational',
                'minor_issue' => 'Minor Issue Found',
                'failed' => 'Failed Inspection',
            ])->default('operational'),
            DatePicker::make('next_inspection_due')->required()
                ->helperText('§17.47 — at intervals not exceeding 3 months'),
            Toggle::make('automatic_monitor_observed')->label('Automatic Monitor Observed (24h)')->default(true),
            Toggle::make('manual_observation_performed')->label('Manual Observation Performed')->default(true),
            Textarea::make('findings')->columnSpanFull(),
            Textarea::make('corrective_action')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('inspection_date')->date('M d, Y')->sortable(),
            TextColumn::make('asr.asr_number')->label('ASR #')->weight('bold'),
            TextColumn::make('facility.name')->label('Facility'),
            TextColumn::make('inspector_name')->label('Inspector'),
            TextColumn::make('result')->badge()->color(fn ($s) => match ($s) {
                'operational' => 'success', 'minor_issue' => 'warning',
                'failed' => 'danger', default => 'gray',
            }),
            IconColumn::make('automatic_monitor_observed')->boolean()->label('Auto Monitor'),
            TextColumn::make('next_inspection_due')->date('M d, Y')->label('Next Due')
                ->color(fn ($state) => $state && $state < now()->addDays(14) ? 'warning' : 'success'),
        ])->defaultSort('inspection_date', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccTowerLightingInspections::route('/')];
    }
}
