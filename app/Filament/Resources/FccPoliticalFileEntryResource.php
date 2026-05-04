<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccPoliticalFileEntryResource\Pages\ManageFccPoliticalFileEntries;
use App\Models\FccPoliticalFileEntry;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FccPoliticalFileEntryResource extends Resource
{
    protected static ?string $model = FccPoliticalFileEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Political File';

    protected static ?string $modelLabel = 'Political File Entry';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 120;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('license_id')->relationship('license', 'call_sign')->required()->searchable(),
            DatePicker::make('order_date')->required(),
            TextInput::make('candidate_or_issue')->required()
                ->placeholder('e.g. Jane Doe (D-CA), Prop 12'),
            TextInput::make('sponsor')->required()
                ->placeholder('Buying entity / committee / PAC'),
            Select::make('office')->options([
                'federal_president' => 'Federal — President',
                'federal_senate' => 'Federal — U.S. Senate',
                'federal_house' => 'Federal — U.S. House',
                'state_governor' => 'State — Governor',
                'state_legislature' => 'State — Legislature',
                'state_other' => 'State — Other',
                'local' => 'Local',
                'ballot_initiative' => 'Ballot Initiative',
                'issue_ad' => 'Issue Advertising',
            ]),
            DatePicker::make('flight_start_date')->label('Flight Start'),
            DatePicker::make('flight_end_date')->label('Flight End'),
            TextInput::make('spots_purchased')->numeric(),
            TextInput::make('rate_per_spot')->numeric()->prefix('$'),
            TextInput::make('total_amount')->numeric()->prefix('$'),
            Toggle::make('lowest_unit_rate_window')->label('LUR Window Active')
                ->helperText('45 days before primary / 60 days before general'),
            DatePicker::make('uploaded_to_public_file_date')->label('Uploaded to Public File')
                ->helperText('Required ASAP per §73.1943'),
            TextInput::make('contract_pdf_path')->label('Contract PDF Path'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('order_date')->date('M d, Y')->sortable(),
            TextColumn::make('license.call_sign')->label('Call Sign')->weight('bold'),
            TextColumn::make('candidate_or_issue')->searchable()->limit(28),
            TextColumn::make('sponsor')->searchable()->limit(24)->toggleable(),
            TextColumn::make('office')->badge()->formatStateUsing(fn ($s) => str($s)->headline()),
            TextColumn::make('spots_purchased')->numeric(),
            TextColumn::make('total_amount')->money('USD'),
            IconColumn::make('lowest_unit_rate_window')->boolean()->label('LUR'),
            TextColumn::make('uploaded_to_public_file_date')->date('M d, Y')->label('In Public File')
                ->color(fn ($state) => $state ? 'success' : 'danger'),
        ])->defaultSort('order_date', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccPoliticalFileEntries::route('/')];
    }
}
