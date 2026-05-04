<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccFacilityResource\Pages\ManageFccFacilities;
use App\Models\FccFacility;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FccFacilityResource extends Resource
{
    protected static ?string $model = FccFacility::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Facilities';

    protected static ?string $modelLabel = 'Facility';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('facility_id')->label('FCC Facility ID')->required()->unique(ignoreRecord: true),
            TextInput::make('name')->required(),
            TextInput::make('community_of_license')->label('Community of License'),
            TextInput::make('state')->maxLength(2),
            TextInput::make('latitude')->numeric(),
            TextInput::make('longitude')->numeric(),
            TextInput::make('antenna_haat_meters')->label('Antenna HAAT (m)')->numeric(),
            TextInput::make('antenna_amsl_meters')->label('Antenna AMSL (m)')->numeric(),
            TextInput::make('asr_number')->label('Antenna Structure Reg. #'),
            TextInput::make('owner'),
            TextInput::make('contact_engineer')->label('Contact Engineer'),
            Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('facility_id')->label('Facility ID')->searchable(),
            TextColumn::make('name')->searchable()->weight('bold'),
            TextColumn::make('community_of_license')->label('Community')->searchable(),
            TextColumn::make('state')->badge(),
            TextColumn::make('asr_number')->label('ASR #')->toggleable(),
            TextColumn::make('licenses_count')->counts('licenses')->label('Licenses'),
            TextColumn::make('contact_engineer')->label('Engineer')->toggleable(),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFccFacilities::route('/'),
        ];
    }
}
