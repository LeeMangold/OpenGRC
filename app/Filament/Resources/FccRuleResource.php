<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FccRuleResource\Pages\ManageFccRules;
use App\Models\FccRule;
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

class FccRuleResource extends Resource
{
    protected static ?string $model = FccRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'FCC Rules';

    protected static ?string $modelLabel = 'FCC Rule';

    protected static string|\UnitEnum|null $navigationGroup = 'FCC Compliance';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('rule_number')->required()->placeholder('e.g. 73.3526')
                ->helperText('CFR section number'),
            TextInput::make('part')->placeholder('e.g. Part 73'),
            TextInput::make('title')->required()->columnSpanFull(),
            Textarea::make('description')->columnSpanFull()->rows(4),
            Select::make('category')->required()->options([
                'technical_standards' => 'Technical Standards',
                'operational_rules' => 'Operational Rules',
                'eas_requirements' => 'EAS Requirements',
                'public_file_rules' => 'Public File Rules',
                'ownership_control' => 'Ownership / Control',
                'reporting_requirements' => 'Reporting Requirements',
            ]),
            Select::make('severity')->required()->options([
                'low' => 'Low', 'medium' => 'Medium',
                'high' => 'High', 'critical' => 'Critical',
            ]),
            Toggle::make('quarterly_filing_required')->label('Requires Quarterly Filing'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('rule_number')->label('CFR Section')->weight('bold')->searchable()->sortable(),
            TextColumn::make('part')->badge(),
            TextColumn::make('title')->searchable()->limit(50),
            TextColumn::make('category')->badge()->formatStateUsing(fn ($s) => str($s)->headline()),
            TextColumn::make('severity')->badge()->color(fn ($s) => match ($s) {
                'critical' => 'danger', 'high' => 'warning',
                'medium' => 'info', 'low' => 'gray', default => 'gray',
            }),
            IconColumn::make('quarterly_filing_required')->label('Qtrly')->boolean(),
        ])->filters([
            SelectFilter::make('category')->options([
                'technical_standards' => 'Technical Standards',
                'operational_rules' => 'Operational Rules',
                'eas_requirements' => 'EAS Requirements',
                'public_file_rules' => 'Public File Rules',
                'ownership_control' => 'Ownership / Control',
                'reporting_requirements' => 'Reporting Requirements',
            ]),
            SelectFilter::make('severity')->options([
                'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical',
            ]),
        ])->defaultSort('rule_number');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFccRules::route('/'),
        ];
    }
}
