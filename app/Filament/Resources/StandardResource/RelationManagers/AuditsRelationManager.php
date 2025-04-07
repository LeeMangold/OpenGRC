<?php

namespace App\Filament\Resources\StandardResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('manager.name')->label('Manager'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->hiddenLabel()
                    ->url(fn ($record) => route('filament.app.resources.audits.view', $record)),
            ]);
    }
}
