<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('name'),
                TextColumn::make('inherent_risk'),
                TextColumn::make('residual_risk'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Relate to Risk')
                    ->modalHeading('Relate to Risk'),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Detach from Implementation'),
                ]),
            ]);
    }
}
