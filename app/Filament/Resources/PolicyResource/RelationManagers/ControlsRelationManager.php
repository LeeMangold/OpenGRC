<?php

namespace App\Filament\Resources\PolicyResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
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
                    ->badge()
                    ->color('primary')
                    ->wrap(),

                TextColumn::make('standard.name')
                    ->label('Standard')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('title')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Attach Control')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['id', 'code', 'title']);
                    })
                    ->recordTitle(function ($record) {
                        return strip_tags("({$record->code}) {$record->title}");
                    })
                    ->recordSelectSearchColumns(['code', 'title']),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.app.resources.controls.view', $record)),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Detach from Policy'),
                ]),
            ]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
