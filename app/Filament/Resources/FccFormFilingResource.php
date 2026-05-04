<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccFormFilingResource\Pages\ManageFccFormFilings;
use App\Models\FccFormFiling;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FccFormFilingResource extends Resource
{
    protected static ?string $model = FccFormFiling::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Form Filings';

    protected static ?string $modelLabel = 'FCC Form Filing';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 150;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('license_id')->relationship('license', 'call_sign')->searchable(),
            TextInput::make('form_number')->required()
                ->placeholder('e.g. 323, 323-E, 397, 2100-H, 2100-A, 2100-B'),
            TextInput::make('form_title')->required()->columnSpanFull()
                ->placeholder('e.g. Biennial Ownership Report (Commercial)'),
            DatePicker::make('filed_date')->required(),
            TextInput::make('file_number')->label('FCC File Number')
                ->placeholder('Assigned by FCC after filing'),
            Select::make('status')->required()->options([
                'filed' => 'Filed',
                'pending_review' => 'Pending FCC Review',
                'granted' => 'Granted',
                'returned' => 'Returned for Correction',
                'dismissed' => 'Dismissed',
                'denied' => 'Denied',
            ])->default('filed'),
            TextInput::make('filed_by'),
            Textarea::make('notes')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('filed_date')->date('M d, Y')->sortable(),
            TextColumn::make('form_number')->badge()->color('warning'),
            TextColumn::make('form_title')->limit(40)->searchable(),
            TextColumn::make('license.call_sign')->label('Call Sign')->weight('bold'),
            TextColumn::make('file_number')->toggleable(),
            TextColumn::make('status')->badge()->color(fn ($s) => match ($s) {
                'granted' => 'success', 'filed' => 'info',
                'pending_review' => 'warning', 'returned' => 'warning',
                'dismissed', 'denied' => 'danger', default => 'gray',
            })->formatStateUsing(fn ($s) => str($s)->headline()),
        ])->filters([
            SelectFilter::make('form_number')->options([
                '323' => '323 (Ownership)', '323-E' => '323-E (NCE Ownership)',
                '397' => '397 (EEO)', '2100-H' => "2100-H (Children's TV)",
                '2100-A' => '2100-A (Renewal)',
            ]),
            SelectFilter::make('status')->options([
                'filed' => 'Filed', 'pending_review' => 'Pending', 'granted' => 'Granted',
            ]),
        ])->defaultSort('filed_date', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccFormFilings::route('/')];
    }
}
