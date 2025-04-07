<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RisksRelationManager extends RelationManager
{
    protected static string $relationship = 'risks';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Associated Risks')
            ->description('Risks that this implementation helps to mitigate.')
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('inherent_risk'),
                Tables\Columns\TextColumn::make('residual_risk'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //                Tables\Actions\CreateAction::make(),
                // Attach to Risk
                Tables\Actions\AttachAction::make()
                    ->label('Associate to Risk')
                    ->modalHeading('Associate to Risk'),

            ])
            ->actions([
                //                Tables\Actions\EditAction::make(),
                //                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
