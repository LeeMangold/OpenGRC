<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Filament\Resources\ApplicationResource;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    public function form(Forms\Form $form): Forms\Form
    {
        return ApplicationResource::form($form);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge()->color(fn ($record) => $record->type->getColor()),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn ($record) => $record->status->getColor()),
                Tables\Columns\TextColumn::make('url')->url(fn ($record) => $record->url, true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
