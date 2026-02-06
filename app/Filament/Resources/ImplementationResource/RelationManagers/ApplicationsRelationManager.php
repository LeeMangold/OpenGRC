<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use App\Filament\Resources\ApplicationResource;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\ViewAction;
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['owner' => fn ($q) => $q->withTrashed(), 'vendor']))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->color(fn ($record) => $record->type->getColor())
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor())
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label(__('Owner'))
                    ->formatStateUsing(fn ($record): string => $record->owner?->displayName() ?? '')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('vendor.name')
                    ->label(__('Vendor'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('url')
                    ->label(__('URL'))
                    ->url(fn ($record) => $record->url, true)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Relate to Application')
                    ->preloadRecordSelect(),
                CreateAction::make()
                    ->label('Create a New Application'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => ApplicationResource::getUrl('view', ['record' => $record])),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
