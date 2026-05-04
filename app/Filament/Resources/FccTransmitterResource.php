<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccTransmitterResource\Pages\ManageFccTransmitters;
use App\Models\FccTransmitter;
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

class FccTransmitterResource extends Resource
{
    protected static ?string $model = FccTransmitter::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Transmitters';

    protected static ?string $modelLabel = 'Transmitter';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('license_id')->relationship('license', 'call_sign')->required()->searchable(),
            TextInput::make('manufacturer'),
            TextInput::make('model'),
            TextInput::make('serial_number'),
            TextInput::make('rated_power_kw')->label('Rated Power (kW)')->numeric(),
            TextInput::make('authorized_erp_kw')->label('Authorized ERP (kW)')->numeric()
                ->helperText('47 CFR 73.211 — Effective Radiated Power'),
            TextInput::make('measured_power_kw')->label('Measured Power (kW)')->numeric(),
            DatePicker::make('last_proof_of_performance')->label('Last Proof of Performance'),
            DatePicker::make('next_proof_due')->label('Next Proof Due'),
            Toggle::make('eas_endec_present')->label('EAS ENDEC Installed (Part 11)'),
            TextInput::make('eas_endec_model')->label('ENDEC Model'),
            Select::make('status')->options([
                'operating' => 'Operating',
                'standby' => 'Standby',
                'offline' => 'Offline',
                'maintenance' => 'In Maintenance',
            ])->default('operating'),
            Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('license.call_sign')->label('Call Sign')->weight('bold')->searchable(),
            TextColumn::make('manufacturer'),
            TextColumn::make('model'),
            TextColumn::make('authorized_erp_kw')->label('Auth ERP (kW)'),
            TextColumn::make('measured_power_kw')->label('Measured (kW)')
                ->color(fn ($state, $record) => $record->authorized_erp_kw && abs($state - $record->authorized_erp_kw) / $record->authorized_erp_kw > 0.05 ? 'danger' : 'success'),
            IconColumn::make('eas_endec_present')->label('EAS')->boolean(),
            TextColumn::make('next_proof_due')->date('M d, Y')->label('Next Proof'),
            TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                'operating' => 'success', 'standby' => 'info',
                'maintenance' => 'warning', 'offline' => 'danger', default => 'gray',
            }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFccTransmitters::route('/'),
        ];
    }
}
