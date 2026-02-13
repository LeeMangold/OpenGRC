<?php

namespace App\Filament\Resources\ControlResource\RelationManagers;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use App\Filament\Resources\ImplementationResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ImplementationRelationManager extends RelationManager
{
    protected static string $relationship = 'Implementations';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->check() && auth()->user()->can('Read Implementations');
    }

    public function form(Schema $schema): Schema
    {
        return ImplementationResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['latestCompletedAudit']))
            ->recordTitleAttribute('details')
            ->columns([
                TextColumn::make('details')
                    ->html()
                    ->wrap()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('effectiveness')
                    ->getStateUsing(fn ($record) => $record->getEffectiveness())
                    ->badge()
                    ->sortable(),
                TextColumn::make('last_assessed')
                    ->label('Last Audit')
                    ->getStateUsing(fn ($record) => $record->getEffectivenessDate() ? $record->getEffectivenessDate() : 'Not yet audited')
                    ->sortable(true)
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ImplementationStatus::class),
                SelectFilter::make('effectiveness')->options(Effectiveness::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New implementation'),
                AttachAction::make()
                    ->label('Add Existing Implementation')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        $query->select(['implementations.id', 'code', 'title']);
                    })
                    ->recordTitle(function ($record) {
                        // Concatenate code and title for the option label
                        return strip_tags("({$record->code}) {$record->title}");
                    })
                    ->recordSelectSearchColumns(['code', 'title']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                //                    ->url(fn ($record) => route('filament.app.resources.implementations.view', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    DetachBulkAction::make()->label('Detach from this Control'),
                ]),
            ]);
    }
}
