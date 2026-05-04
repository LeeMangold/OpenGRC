<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccEasTestResource\Pages\ManageFccEasTests;
use App\Models\FccEasTest;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FccEasTestResource extends Resource
{
    protected static ?string $model = FccEasTest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'EAS Tests';

    protected static ?string $modelLabel = 'EAS Test';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('license_id')->relationship('license', 'call_sign')->required()->searchable()->preload(),
            Select::make('test_type')->required()->options([
                'RWT' => 'RWT — Required Weekly Test',
                'RMT' => 'RMT — Required Monthly Test',
                'NPT' => 'NPT — National Periodic Test',
                'state_test' => 'State EAS Test',
            ])->helperText('Per 47 CFR Part 11'),
            Select::make('direction')->required()->options([
                'received' => 'Received (from monitor)',
                'originated' => 'Originated (this station)',
            ])->default('received'),
            DateTimePicker::make('test_datetime')->required()->seconds(false),
            TextInput::make('originator_code')->maxLength(8)
                ->helperText('e.g. EAN, PEP, EAS, CIV, WXR'),
            TextInput::make('event_code')->maxLength(8)
                ->helperText('e.g. RWT, RMT, EAS, EAN, NPT'),
            TextInput::make('location_codes')->helperText('FIPS codes (comma-separated)'),
            Toggle::make('audio_intelligible')->default(true),
            Toggle::make('visual_message_present')->default(true),
            Toggle::make('filed_in_etrs')->label('Filed in FCC ETRS'),
            TextInput::make('logged_by')->placeholder('Operator name'),
            Textarea::make('comments')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('test_datetime')->dateTime('M d, Y H:i')->sortable()->label('Date/Time'),
            TextColumn::make('license.call_sign')->label('Call Sign')->weight('bold'),
            TextColumn::make('test_type')->badge()->color(fn ($s) => match ($s) {
                'RWT' => 'gray', 'RMT' => 'info', 'NPT' => 'warning',
                'state_test' => 'info', default => 'gray',
            }),
            TextColumn::make('direction')->badge()->color(fn ($s) => $s === 'originated' ? 'success' : 'gray'),
            TextColumn::make('event_code'),
            TextColumn::make('originator_code'),
            IconColumn::make('audio_intelligible')->boolean()->label('Audio'),
            IconColumn::make('filed_in_etrs')->boolean()->label('ETRS'),
            TextColumn::make('logged_by')->toggleable(),
        ])->filters([
            SelectFilter::make('test_type')->options([
                'RWT' => 'RWT', 'RMT' => 'RMT', 'NPT' => 'NPT', 'state_test' => 'State',
            ]),
            SelectFilter::make('license_id')->relationship('license', 'call_sign')->label('Call Sign'),
        ])->defaultSort('test_datetime', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageFccEasTests::route('/')];
    }
}
