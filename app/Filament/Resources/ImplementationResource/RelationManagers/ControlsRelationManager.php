<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ControlsRelationManager extends RelationManager
{
    protected static string $relationship = 'controls';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['standard']))
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('code')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('standard.name')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('title')
                    ->sortable()
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AttachAction::make()
                    ->label('Relate to Control')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['id', 'code', 'title']); // Select only necessary columns
                    })
                    ->recordTitle(function ($record) {
                        // Concatenate code and title for the option label
                        return strip_tags("({$record->code}) {$record->title}");
                    })
                    ->recordSelectSearchColumns(['code', 'title']),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.app.resources.controls.view', $record)),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    DetachBulkAction::make()->label('Detach from this Control'),
                ]),
            ]);
    }

    // Don't allow creating new controls from the implementation resource
    public function canCreate(): bool
    {
        return false;
    }
}
