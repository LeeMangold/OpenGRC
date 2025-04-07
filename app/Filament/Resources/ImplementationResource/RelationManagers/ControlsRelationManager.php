<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ControlsRelationManager extends RelationManager
{
    protected static string $relationship = 'controls';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('standard.name')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('title')
                    ->sortable()
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->label('Relate to Control')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['id', 'code', 'title']); // Select only necessary columns
                    })
                    ->recordTitle(function ($record) {
                        // Concatenate code and title for the option label
                        return "({$record->code}) {$record->title}";
                    })
                    ->recordSelectSearchColumns(['code', 'title']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.app.resources.controls.view', $record)),

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\DetachBulkAction::make()->label('Detach from this Control'),
                ]),
            ]);
    }

    // Don't allow creating new controls from the implementation resource
    public function canCreate(): bool
    {
        return false;
    }
}
