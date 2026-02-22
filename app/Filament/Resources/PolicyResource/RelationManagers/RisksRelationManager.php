<?php

namespace App\Filament\Resources\PolicyResource\RelationManagers;

use App\Enums\RiskLevel;
use App\Models\Risk;
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('residual_risk')
                    ->label('Residual Risk')
                    ->badge()
                    ->color(function (Risk $record) {
                        return RiskLevel::getFilamentColor($record->residual_likelihood, $record->residual_impact);
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Attach Risk')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['id', 'name']);
                    })
                    ->recordTitle(function ($record) {
                        return strip_tags($record->name);
                    })
                    ->recordSelectSearchColumns(['name']),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.app.resources.risks.view', $record)),
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
