<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuditsRelationManager extends RelationManager
{
    protected static string $relationship = 'audits';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('audit.table.columns.status'))
                    ->sortable()
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('manager.name')
                    ->label(__('audit.table.columns.manager'))
                    ->default('Unassigned')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label(__('audit.table.columns.start_date'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label(__('audit.table.columns.end_date'))
                    ->date()
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Create New Audit')
                    ->url(fn (): string => \App\Filament\Resources\AuditResource::getUrl('create', ['default_program_id' => $this->ownerRecord->id])
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record): string => \App\Filament\Resources\AuditResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
