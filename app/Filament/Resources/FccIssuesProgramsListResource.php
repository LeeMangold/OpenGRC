<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccIssuesProgramsListResource\Pages\ManageFccIssuesProgramsLists;
use App\Models\FccIssuesProgramsList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FccIssuesProgramsListResource extends Resource
{
    protected static ?string $model = FccIssuesProgramsList::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Issues/Programs';

    protected static ?string $modelLabel = 'Issues/Programs List';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 110;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('license_id')->relationship('license', 'call_sign')->required()->searchable(),
            TextInput::make('quarter_year')->numeric()->required()->default(now()->year),
            Select::make('quarter')->required()->options([
                'Q1' => 'Q1 (Jan-Mar)',
                'Q2' => 'Q2 (Apr-Jun)',
                'Q3' => 'Q3 (Jul-Sep)',
                'Q4' => 'Q4 (Oct-Dec)',
            ]),
            DatePicker::make('placed_in_file_date')->label('Placed in Public File')
                ->helperText('Required within 10 days of quarter end (§73.3526)'),
            Select::make('status')->required()->options([
                'draft' => 'Draft',
                'placed_in_file' => 'Placed in Public File',
                'late_filed' => 'Late-Filed',
            ])->default('draft'),
            Textarea::make('preparer_notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('quarter_year')->label('Year')->sortable(),
            TextColumn::make('quarter')->badge(),
            TextColumn::make('license.call_sign')->label('Call Sign')->weight('bold')->searchable(),
            TextColumn::make('placed_in_file_date')->date('M d, Y')->label('Placed In File'),
            TextColumn::make('entries_count')->counts('entries')->label('# Programs'),
            TextColumn::make('status')->badge()->color(fn ($s) => match ($s) {
                'placed_in_file' => 'success', 'draft' => 'warning',
                'late_filed' => 'danger', default => 'gray',
            })->formatStateUsing(fn ($s) => str($s)->headline()),
        ])->filters([
            SelectFilter::make('status')->options([
                'draft' => 'Draft', 'placed_in_file' => 'Placed In File', 'late_filed' => 'Late',
            ]),
        ])->defaultSort('quarter_year', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccIssuesProgramsLists::route('/')];
    }
}
