<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccAsrRegistrationResource\Pages\ManageFccAsrRegistrations;
use App\Models\FccAsrRegistration;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FccAsrRegistrationResource extends Resource
{
    protected static ?string $model = FccAsrRegistration::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-radio';

    protected static ?string $navigationLabel = 'ASR Registrations';

    protected static ?string $modelLabel = 'ASR (Antenna Structure Reg.)';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('asr_number')->required()->unique(ignoreRecord: true)
                ->placeholder('7-digit FCC ASR #')
                ->helperText('47 CFR Part 17'),
            TextInput::make('owner')->required(),
            Select::make('structure_type')->options([
                'guyed' => 'Guyed Tower',
                'self_supporting' => 'Self-Supporting',
                'monopole' => 'Monopole',
                'building' => 'Building / Roof Mount',
                'pole' => 'Pole',
            ]),
            TextInput::make('overall_height_meters')->numeric()->label('Overall Height (m)')
                ->helperText('AGL — registration required >60.96m (200ft)'),
            TextInput::make('latitude')->numeric(),
            TextInput::make('longitude')->numeric(),
            TextInput::make('faa_study_number')->label('FAA Study #'),
            Select::make('lighting_type')->options([
                'none' => 'None',
                'red_only' => 'Red beacon only (FAA Style A)',
                'medium_dual' => 'Medium-intensity Dual',
                'high_dual' => 'High-intensity Dual',
                'medium_white' => 'Medium-intensity White',
                'high_white' => 'High-intensity White',
            ]),
            Select::make('painting_required')->options([
                'none' => 'None',
                'aviation_orange_white' => 'Aviation Orange / White (Std 1)',
            ]),
            DatePicker::make('last_inspection_date')->label('Last §17.47 Inspection'),
            DatePicker::make('next_inspection_due')->label('Next Inspection Due'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('asr_number')->label('ASR #')->weight('bold')->searchable()->sortable(),
            TextColumn::make('owner')->searchable()->limit(28),
            TextColumn::make('structure_type')->badge()->formatStateUsing(fn ($s) => str($s)->headline()),
            TextColumn::make('overall_height_meters')->label('Height (m)')->numeric(2),
            TextColumn::make('lighting_type')->badge(),
            TextColumn::make('next_inspection_due')->date('M d, Y')->label('Next Inspection')
                ->color(fn ($state) => $state && $state < now()->addDays(30) ? 'danger' : 'success'),
        ])
        ->defaultSort('asr_number')
        ->striped()
        ->paginated([25, 50, 100, 250]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccAsrRegistrations::route('/')];
    }
}
