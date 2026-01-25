<?php

namespace App\Filament\Resources\ControlResource\RelationManagers;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use App\Filament\Resources\ImplementationResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImplementationRelationManager extends RelationManager
{
    protected static string $relationship = 'Implementations';

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return auth()->check() && auth()->user()->can('Read Implementations');
    }

    public function form(Form $form): Form
    {
        return ImplementationResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('details')
            ->columns([
                Tables\Columns\TextColumn::make('details')
                    ->html()
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effectiveness')
                    ->getStateUsing(fn ($record) => $record->getEffectiveness())
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_assessed')
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
                Tables\Actions\CreateAction::make()
                    ->label('New implementation'),
                Tables\Actions\AttachAction::make()
                    ->label('Add Existing Implementation')
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
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                //                    ->url(fn ($record) => route('filament.app.resources.implementations.view', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\DetachBulkAction::make()->label('Detach from this Control'),
                ]),
            ]);
    }
}
