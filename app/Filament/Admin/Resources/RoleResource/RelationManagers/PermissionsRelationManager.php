<?php

namespace App\Filament\Admin\Resources\RoleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class PermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Attach Permission to this Role')
                    ->after(function ($record) {
                        Cache::forget('spatie.permission.cache');
                    })
                    ->preloadRecordSelect(),

            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->after(function ($record) {
                        Cache::forget('spatie.permission.cache');
                    })
                    ->label('Detach from Role'),
            ]);
    }

    protected function saved(): void
    {
        // Clear the permissions cache
        Cache::forget('spatie.permission.cache');
    }
}
