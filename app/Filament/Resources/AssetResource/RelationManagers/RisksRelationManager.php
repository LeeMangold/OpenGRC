<?php

namespace App\Filament\Resources\AssetResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RisksRelationManager extends RelationManager
{
    protected static string $relationship = 'risks';

    protected static ?string $title = 'Risks';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary')
                    ->wrap(),

                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('residual_risk')
                    ->label('Residual Risk')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Relate to Risk')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['risks.id', 'risks.code', 'risks.name']);
                    })
                    ->recordTitle(function ($record) {
                        return strip_tags("({$record->code}) {$record->name}");
                    })
                    ->recordSelectSearchColumns(['code', 'name']),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.app.resources.risks.view', $record)),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()->label('Detach from Asset'),
                ]),
            ]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
