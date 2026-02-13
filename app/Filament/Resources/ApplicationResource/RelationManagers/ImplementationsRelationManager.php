<?php

namespace App\Filament\Resources\ApplicationResource\RelationManagers;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ImplementationsRelationManager extends RelationManager
{
    protected static string $relationship = 'implementations';

    protected static ?string $title = 'Implementations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('details')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('details')
            ->columns([
                TextColumn::make('code')
                    ->label(__('Code'))
                    ->searchable(),

                TextColumn::make('details')
                    ->label(__('Details'))
                    ->searchable()
                    ->html()
                    ->wrap(),

                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('effectiveness')
                    ->label(__('Effectiveness'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('controls_count')
                    ->counts('controls')
                    ->label(__('Controls')),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ImplementationStatus::class),
                SelectFilter::make('effectiveness')
                    ->options(Effectiveness::class),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
