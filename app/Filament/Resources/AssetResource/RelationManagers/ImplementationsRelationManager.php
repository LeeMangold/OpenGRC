<?php

namespace App\Filament\Resources\AssetResource\RelationManagers;

use App\Enums\Effectiveness;
use App\Enums\ImplementationStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ImplementationsRelationManager extends RelationManager
{
    protected static string $relationship = 'implementations';

    protected static ?string $title = 'Implementations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('details')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('details')
            ->columns([
                Tables\Columns\TextColumn::make('details')
                    ->label('Details')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('effectiveness')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('controls_count')
                    ->counts('controls')
                    ->label('Controls'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(ImplementationStatus::class),
                Tables\Filters\SelectFilter::make('effectiveness')
                    ->options(Effectiveness::class),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
