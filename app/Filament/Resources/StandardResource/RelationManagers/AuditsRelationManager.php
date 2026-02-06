<?php

namespace App\Filament\Resources\StandardResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['manager' => fn ($q) => $q->withTrashed()]))
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('status'),
                TextColumn::make('manager.name')->label('Manager')
                    ->formatStateUsing(fn ($record): string => $record->manager?->displayName() ?? ''),
            ])
            ->recordActions([
                ViewAction::make()->hiddenLabel()
                    ->url(fn ($record) => route('filament.app.resources.audits.view', $record)),
            ])
            ->headerActions([
                CreateAction::make()->label('Add New Audit')
                    ->url(fn ($livewire) => route('filament.app.resources.audits.create', ['standard' => $livewire->ownerRecord])),
            ]);
    }
}
