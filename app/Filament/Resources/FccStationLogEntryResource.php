<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccStationLogEntryResource\Pages\ManageFccStationLogEntries;
use App\Models\FccStationLogEntry;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FccStationLogEntryResource extends Resource
{
    protected static ?string $model = FccStationLogEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Station Log';

    protected static ?string $modelLabel = 'Station Log Entry';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 130;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('license_id')->relationship('license', 'call_sign')->required()->searchable(),
            DateTimePicker::make('logged_at')->required()->seconds(false)->default(now()),
            Select::make('entry_type')->required()->options([
                'transmitter_reading' => 'Transmitter Reading',
                'eas_test' => 'EAS Test',
                'tower_lighting_check' => 'Tower Lighting Check',
                'directional_pattern_check' => 'Directional Pattern Check (AM)',
                'sign_on' => 'Sign-On',
                'sign_off' => 'Sign-Off',
                'station_id' => 'Station ID',
                'maintenance' => 'Maintenance',
                'power_change' => 'Power Change',
                'incident' => 'Incident',
                'other' => 'Other',
            ])->helperText('Per 47 CFR §73.1820'),
            Textarea::make('summary')->required()->columnSpanFull(),
            KeyValue::make('readings')->columnSpanFull()->keyLabel('Reading')->valueLabel('Value')
                ->helperText('e.g. power_kw, swr, plate_voltage, plate_current'),
            TextInput::make('logged_by'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('logged_at')->dateTime('M d H:i')->label('Logged At')->sortable(),
            TextColumn::make('license.call_sign')->label('Call Sign')->weight('bold'),
            TextColumn::make('entry_type')->badge()->formatStateUsing(fn ($s) => str($s)->headline()),
            TextColumn::make('summary')->limit(40)->wrap(),
            TextColumn::make('logged_by')->toggleable(),
        ])->filters([
            SelectFilter::make('entry_type')->options([
                'transmitter_reading' => 'Transmitter',
                'eas_test' => 'EAS', 'tower_lighting_check' => 'Tower Lighting',
                'directional_pattern_check' => 'AM Pattern',
            ]),
            SelectFilter::make('license_id')->relationship('license', 'call_sign')->label('Call Sign'),
        ])->defaultSort('logged_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccStationLogEntries::route('/')];
    }
}
