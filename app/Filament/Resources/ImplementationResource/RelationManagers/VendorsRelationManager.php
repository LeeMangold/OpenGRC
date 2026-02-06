<?php

namespace App\Filament\Resources\ImplementationResource\RelationManagers;

use App\Filament\Resources\VendorResource;
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

class VendorsRelationManager extends RelationManager
{
    protected static string $relationship = 'vendors';

    public function form(Schema $schema): Schema
    {
        return VendorResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['vendorManager' => fn ($q) => $q->withTrashed()]))
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn ($record) => $record->status->getColor())
                    ->sortable(),

                TextColumn::make('risk_rating')
                    ->label(__('Risk Rating'))
                    ->badge()
                    ->color(fn ($record) => $record->risk_rating->getColor())
                    ->sortable(),

                TextColumn::make('vendorManager.name')
                    ->label(__('Vendor Manager'))
                    ->formatStateUsing(fn ($record): string => $record->vendorManager?->displayName() ?? '')
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
                    ->label('Relate to Vendor')
                    ->preloadRecordSelect(),
                CreateAction::make()
                    ->label('Create a New Vendor'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn ($record) => VendorResource::getUrl('view', ['record' => $record])),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
