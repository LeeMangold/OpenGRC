<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RiskResource\Pages;
use App\Filament\Resources\RiskResource\RelationManagers;
use App\Models\Risk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RiskResource extends Resource
{
    protected static ?string $model = Risk::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull()
                    ->label('Description'),
                Forms\Components\Section::make('inherent')
                    ->columnSpan(1)
                    ->heading('Inherent Risk Scoring')
                    ->schema([
                        Forms\Components\ToggleButtons::make('inherent_likelihood')
                            ->label('Likelihood')
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                        Forms\Components\ToggleButtons::make('inherent_impact')
                            ->label('Impact')
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                    ]),
                Forms\Components\Section::make('residual')
                    ->columnSpan(1)
                    ->heading('Residual Risk Scoring')
                    ->schema([
                        Forms\Components\ToggleButtons::make('residual_likelihood')
                            ->label('Likelihood')
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                        Forms\Components\ToggleButtons::make('residual_impact')
                            ->label('Impact')
                            ->options([
                                '1' => 'Very Low',
                                '2' => 'Low',
                                '3' => 'Moderate',
                                '4' => 'High',
                                '5' => 'Very High',
                            ])
                            ->grouped()
                            ->required(),
                    ]),

                Forms\Components\Select::make('implementations')
                    ->label('Related Implementations')
                    ->helperText("What are we doing to mitigate this risk?")
                    ->relationship('implementations', 'title')
                    ->multiple(),


                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'Open' => 'Open',
                        'Closed' => 'Closed',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('residual_risk', 'desc')
            ->emptyStateHeading('No Risks Identified Yet')
            ->emptyStateDescription('Add and analyse your first risk by clicking the "Track New Risk" button above.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inherent_risk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('residual_risk')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRisks::route('/'),
            'create' => Pages\CreateRisk::route('/create'),
            'edit' => Pages\EditRisk::route('/{record}/edit'),
        ];
    }
}
