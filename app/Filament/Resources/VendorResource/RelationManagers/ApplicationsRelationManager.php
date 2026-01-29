<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use App\Filament\Resources\ApplicationResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'applications';

    public function form(Schema $schema): Schema
    {
        return ApplicationResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['owner']))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('owner.name')->label('Owner')->searchable(),
                TextColumn::make('type')->badge()->color(fn ($record) => $record->type->getColor()),
                TextColumn::make('status')->badge()->color(fn ($record) => $record->status->getColor()),
                TextColumn::make('url')->url(fn ($record) => $record->url, true),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
